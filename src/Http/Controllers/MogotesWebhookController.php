<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Http\Controllers;

use Dantofema\MogotesLaravel\Events\WebhookReceived;
use Dantofema\MogotesLaravel\Exceptions\InvalidWebhookSignatureException;
use Dantofema\MogotesLaravel\Services\WebhookSignatureValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para recibir webhooks desde Mogotes.
 */
class MogotesWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Obtener headers necesarios
            $signature = $request->header('Mogotes-Signature');
            $timestamp = $request->header('Mogotes-Timestamp');

            // Validar presencia de headers
            if ($signature === null) {
                throw InvalidWebhookSignatureException::missingSignature();
            }

            if ($timestamp === null) {
                throw InvalidWebhookSignatureException::missingTimestamp();
            }

            // Validar firma
            $validator = $this->createValidator();
            $validator->validate(
                rawBody: (string) $request->getContent(),
                signature: $signature,
                timestamp: (int) $timestamp
            );

            // Decodificar payload
            /** @var array<string, mixed> $payload */
            $payload = $request->json()->all();

            // Disparar evento
            event(new WebhookReceived(
                payload: $payload,
                signature: $signature,
                timestamp: (int) $timestamp
            ));

            // Log exitoso
            Log::info('Webhook de Mogotes recibido y procesado exitosamente', [
                'event' => $payload['event'] ?? 'unknown',
                'mogotes_id' => $payload['mogotes_id'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook recibido correctamente',
            ]);

        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('Webhook de Mogotes rechazado: firma inválida', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => [
                    'code' => 'invalid_signature',
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    private function createValidator(): WebhookSignatureValidator
    {
        /** @var string $secret */
        $secret = (string) config('mogotes-laravel.webhooks.secret');

        return new WebhookSignatureValidator($secret);
    }
}
