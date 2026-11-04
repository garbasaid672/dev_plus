<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\GeminiReplyException;
use App\Services\GeminiReplyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomingMessageController extends Controller
{
    public function __invoke(Request $request, GeminiReplyService $replyService): JsonResponse
    {
        $request->mergeIfMissing(['source' => 'site']);

        $data = $request->validate([
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'source' => ['required', 'string', 'max:80'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'conversation_id' => ['nullable', 'string', 'max:255'],
        ]);

        $externalId = $data['external_id'] ?? null;

        if ($externalId !== null) {
            $message = Message::firstOrCreate(
                [
                    'source' => $data['source'],
                    'external_id' => $externalId,
                ],
                $data,
            );
        } else {
            $message = Message::create($data);
        }

        if ($message->status === Message::STATUS_REPLIED) {
            return response()->json([
                'id' => $message->getKey(),
                'status' => $message->status,
                'reply' => $message->reply,
            ]);
        }

        try {
            $message = $replyService->reply($message);
        } catch (GeminiReplyException $exception) {
            return response()->json([
                'id' => $message->getKey(),
                'status' => $message->status,
                'message' => 'Automatic reply could not be generated.',
            ], $exception->httpStatus);
        }

        return response()->json([
            'id' => $message->getKey(),
            'status' => $message->status,
            'reply' => $message->reply,
        ], $message->wasRecentlyCreated ? 201 : 200);
    }
}
