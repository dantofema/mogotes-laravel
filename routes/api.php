<?php

use Dantofema\MogotesLaravel\Http\Controllers\MogotesWebhookController;
use Illuminate\Support\Facades\Route;

if (config('mogotes-laravel.webhooks.register_route', true)) {
    Route::post(config('mogotes-laravel.webhooks.path', '/mogotes/webhook'), MogotesWebhookController::class)
        ->name('mogotes.webhook');
}
