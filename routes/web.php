<?php

use AlexKassel\CodeAgent\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [WebhookController::class, 'handle'])
    ->name('code-agent.webhook')
    ->middleware('api');
