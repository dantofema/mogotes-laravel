<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Services;

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\MogotesClient;
use Exception;
use Illuminate\Support\Str;

final readonly class NotificationsService
{
    public function __construct(
        private MogotesClient $client
    ) {}

    /**
     * Envía una notificación por email usando una plantilla de Mogotes.
     *
     * @param  string  $template  El identificador de la plantilla.
     * @param  string  $to  El destinatario (email).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @param  string|null  $idempotencyKey  Clave de idempotencia (se genera automáticamente si es null).
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function email(string $template, string $to, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->send(
            channel: 'email',
            template: $template,
            to: $to,
            data: $data,
            idempotencyKey: $idempotencyKey
        );
    }

    /**
     * Envía una notificación por WhatsApp usando una plantilla de Mogotes.
     *
     * @param  string  $template  El identificador de la plantilla.
     * @param  string  $to  El destinatario (teléfono).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @param  string|null  $idempotencyKey  Clave de idempotencia (se genera automáticamente si es null).
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function whatsapp(string $template, string $to, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->send(
            channel: 'whatsapp',
            template: $template,
            to: $to,
            data: $data,
            idempotencyKey: $idempotencyKey
        );
    }

    /**
     * Envía una notificación genérica a través del canal especificado.
     *
     * @param  string  $channel  El canal de envío (email, whatsapp, sms, push).
     * @param  string  $template  El identificador de la plantilla.
     * @param  string  $to  El destinatario.
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @param  string|null  $idempotencyKey  Clave de idempotencia (se genera automáticamente si es null).
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function send(
        string $channel,
        string $template,
        string $to,
        array $data = [],
        ?string $idempotencyKey = null
    ): array {
        try {
            $idempotencyKey = $idempotencyKey ?? (string) Str::uuid();

            $payload = [
                'channel' => $channel,
                'template_key' => $template,
                'to' => $to,
                'data' => $data,
                'idempotency_key' => $idempotencyKey,
            ];

            $request = $this->client->buildRequest();

            $response = $request->post('/v1/notifications', $payload);

            if ($response->status() === 401) {
                throw MogotesUnauthorizedException::fromResponse($response);
            }

            if ($response->failed()) {
                throw MogotesApiException::fromResponse($response);
            }

            /** @var array<string, mixed> $responseData */
            $responseData = $response->json();

            return $responseData;

        } catch (MogotesUnauthorizedException|MogotesApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw MogotesConnectionException::unreachable($e->getMessage());
        }
    }
}
