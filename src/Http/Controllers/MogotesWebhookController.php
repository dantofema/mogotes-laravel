<?php

namespace Dantofema\MogotesLaravel\Http\Controllers;

use Dantofema\MogotesLaravel\Events\WebhookReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MogotesWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // TODO: Validar firma HMAC usando config('mogotes-laravel.webhooks.secret')

        $payload = $request->all();

        event(new WebhookReceived($payload));

        return response()->json(['status' => 'ok']);
    }
}
