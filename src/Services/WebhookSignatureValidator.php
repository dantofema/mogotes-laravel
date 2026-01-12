<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Services;

use Dantofema\MogotesLaravel\Exceptions\InvalidWebhookSignatureException;

/**
 * Servicio para validar webhooks entrantes desde Mogotes.
 */
class WebhookSignatureValidator
{
    /**
     * Ventana de tiempo máxima (en segundos) para aceptar un webhook.
     */
    private const DEFAULT_MAX_AGE_SECONDS = 300;

    public function __construct(
        private readonly string $secret,
        private readonly int $maxAgeSeconds = self::DEFAULT_MAX_AGE_SECONDS,
    ) {}

    /**
     * Valida la firma HMAC del webhook.
     *
     * @param  string  $rawBody  Body crudo del request
     * @param  string  $signature  Firma recibida en el header Mogotes-Signature
     * @param  int  $timestamp  Timestamp recibido en el header Mogotes-Timestamp
     *
     * @throws InvalidWebhookSignatureException
     */
    public function validate(string $rawBody, string $signature, int $timestamp): void
    {
        // Validar que el timestamp esté dentro de la ventana permitida
        $this->validateTimestamp($timestamp);

        // Construir el string a firmar: "<timestamp>.<rawBody>"
        $signedPayload = $timestamp.'.'.$rawBody;

        // Calcular la firma esperada
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->secret);

        // Comparar de forma segura contra timing attacks
        if (! hash_equals($expectedSignature, $signature)) {
            throw InvalidWebhookSignatureException::signatureMismatch();
        }
    }

    /**
     * Valida que el timestamp esté dentro de la ventana de tiempo permitida.
     *
     * @throws InvalidWebhookSignatureException
     */
    private function validateTimestamp(int $timestamp): void
    {
        $currentTime = time();
        $age = abs($currentTime - $timestamp);

        if ($age > $this->maxAgeSeconds) {
            throw InvalidWebhookSignatureException::timestampOutOfWindow($timestamp, $this->maxAgeSeconds);
        }
    }
}
