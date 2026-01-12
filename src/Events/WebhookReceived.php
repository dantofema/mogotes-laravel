<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se recibe un webhook válido desde Mogotes.
 */
class WebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload  Payload completo del webhook
     * @param  string  $signature  Firma HMAC recibida
     * @param  int  $timestamp  Timestamp Unix del webhook
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $signature,
        public readonly int $timestamp,
    ) {}
}
