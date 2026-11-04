<?php

use App\Http\Controllers\IncomingMessageController;
use App\Http\Middleware\VerifyMessageIntegrationToken;
use Illuminate\Support\Facades\Route;

Route::post('/messages/incoming', IncomingMessageController::class)
    ->middleware([VerifyMessageIntegrationToken::class, 'throttle:30,1'])
    ->name('messages.incoming');
