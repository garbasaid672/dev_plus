<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudApiService
{
    public function sendText(string $recipient, string $text): void
    {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $apiVersion = config('services.whatsapp.graph_api_version');

        if (
            ! is_string($accessToken) || $accessToken === ''
            || ! is_string($phoneNumberId) || ! ctype_digit($phoneNumberId)
            || ! is_string($apiVersion) || preg_match('/^v\d+\.\d+$/D', $apiVersion) !== 1
        ) {
            throw new WhatsAppCloudApiException('WhatsApp Cloud API is not configured correctly.');
        }

        $formattedText = preg_replace('/\*\*(.+?)\*\*/u', '*$1*', $text);

        if (! is_string($formattedText)) {
            throw new WhatsAppCloudApiException('The WhatsApp reply could not be formatted.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($accessToken)
                ->connectTimeout(3)
                ->timeout(15)
                ->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $formattedText,
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new WhatsAppCloudApiException('WhatsApp Cloud API request failed.', $exception);
        }

        $sentMessageId = data_get($response->json(), 'messages.0.id');

        if (! is_string($sentMessageId) || $sentMessageId === '') {
            throw new WhatsAppCloudApiException('WhatsApp Cloud API returned no message ID.');
        }
    }
}
