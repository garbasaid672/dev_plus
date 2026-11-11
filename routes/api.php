<?php

use App\Http\Controllers\IncomingMessageController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMessageIntegrationToken;
use Illuminate\Support\Facades\Route;

Route::post('/messages/incoming', IncomingMessageController::class)
    ->middleware([VerifyMessageIntegrationToken::class, 'throttle:30,1'])
    ->name('messages.incoming');

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');

Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->middleware('throttle:120,1')
    ->name('webhooks.whatsapp.receive');
