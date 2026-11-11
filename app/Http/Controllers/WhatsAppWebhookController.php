<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\GeminiReplyException;
use App\Services\GeminiReplyService;
use App\Services\WhatsAppCloudApiException;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $providedToken = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        if (! is_string($verifyToken) || $verifyToken === '') {
            return response('WhatsApp webhook is not configured.', 503);
        }

        if (
            $mode !== 'subscribe'
            || ! is_string($providedToken)
            || ! hash_equals($verifyToken, $providedToken)
        ) {
            return response('Forbidden.', 403);
        }

        if (! is_string($challenge) || $challenge === '') {
            return response('Invalid challenge.', 400);
        }

        return response($challenge)->header('Content-Type', 'text/plain');
    }

    public function receive(
        Request $request,
        GeminiReplyService $replyService,
        WhatsAppCloudApiService $whatsApp,
    ): Response {
        $appSecret = config('services.whatsapp.app_secret');

        if (! is_string($appSecret) || $appSecret === '') {
            return response()->json(['message' => 'WhatsApp webhook is not configured.'], 503);
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if (! is_string($signature) || ! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $apiVersion = config('services.whatsapp.graph_api_version');

        if (
            ! is_string($accessToken) || $accessToken === ''
            || ! is_string($phoneNumberId) || ! ctype_digit($phoneNumberId)
            || ! is_string($apiVersion) || preg_match('/^v\d+\.\d+$/D', $apiVersion) !== 1
        ) {
            return response()->json(['message' => 'WhatsApp Cloud API is not configured.'], 503);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->json(['status' => 'ok']);
        }

        $entries = $payload['entry'] ?? [];

        if (! is_array($entries)) {
            return response()->json(['message' => 'Invalid WhatsApp webhook payload.'], 400);
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $changes = $entry['changes'] ?? [];

            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                if (! is_array($change) || ($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                if (! is_array($value)) {
                    continue;
                }

                $incomingMessages = $value['messages'] ?? [];

                if (! is_array($incomingMessages)) {
                    continue;
                }

                foreach ($incomingMessages as $incomingMessage) {
                    if (! is_array($incomingMessage)) {
                        continue;
                    }

                    try {
                        $this->processIncomingMessage($incomingMessage, $value, $replyService, $whatsApp);
                    } catch (GeminiReplyException|WhatsAppCloudApiException $exception) {
                        Log::error('WhatsApp webhook message processing failed.', [
                            'whatsapp_message_id' => data_get($incomingMessage, 'id'),
                            'exception' => $exception,
                        ]);

                        return response()->json([
                            'message' => 'WhatsApp message could not be processed.',
                        ], 500);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string, mixed>  $incomingMessage
     * @param  array<string, mixed>  $value
     */
    private function processIncomingMessage(
        array $incomingMessage,
        array $value,
        GeminiReplyService $replyService,
        WhatsAppCloudApiService $whatsApp,
    ): void {
        $messageId = data_get($incomingMessage, 'id');
        $senderPhone = data_get($incomingMessage, 'from');
        $body = $this->messageBody($incomingMessage);
        $configuredPhoneNumberId = config('services.whatsapp.phone_number_id');
        $webhookPhoneNumberId = data_get($value, 'metadata.phone_number_id');

        if (
            ! is_string($messageId) || $messageId === ''
            || ! is_string($senderPhone) || $senderPhone === ''
            || ! is_string($body) || $body === ''
            || ! is_string($configuredPhoneNumberId)
            || ! is_string($webhookPhoneNumberId)
            || ! hash_equals($configuredPhoneNumberId, $webhookPhoneNumberId)
        ) {
            return;
        }

        if (mb_strlen($body) > 20000) {
            Log::warning('A WhatsApp message exceeded the supported input length.', [
                'whatsapp_message_id' => $messageId,
            ]);

            return;
        }

        $contacts = $value['contacts'] ?? [];
        $contact = collect(is_array($contacts) ? $contacts : [])
            ->first(fn (mixed $contact): bool => is_array($contact)
                && data_get($contact, 'wa_id') === $senderPhone);
        $senderName = data_get($contact, 'profile.name');

        $message = Message::firstOrCreate(
            [
                'source' => 'whatsapp',
                'external_id' => $messageId,
            ],
            [
                'sender_name' => is_string($senderName) ? $senderName : null,
                'body' => $body,
                'conversation_id' => $senderPhone,
            ],
        );

        if ($message->whatsapp_sent_at !== null) {
            return;
        }

        if ($message->status !== Message::STATUS_REPLIED) {
            $message = $replyService->reply($message);
        }

        if (! is_string($message->reply) || trim($message->reply) === '') {
            throw new WhatsAppCloudApiException('No generated reply is available to send.');
        }

        $whatsApp->sendText($senderPhone, $message->reply);

        $message->forceFill(['whatsapp_sent_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $incomingMessage
     */
    private function messageBody(array $incomingMessage): ?string
    {
        $body = match (data_get($incomingMessage, 'type')) {
            'text' => data_get($incomingMessage, 'text.body'),
            'interactive' => data_get($incomingMessage, 'interactive.button_reply.title')
                ?? data_get($incomingMessage, 'interactive.list_reply.title'),
            'button' => data_get($incomingMessage, 'button.text'),
            default => null,
        };

        return is_string($body) ? trim($body) : null;
    }
}
