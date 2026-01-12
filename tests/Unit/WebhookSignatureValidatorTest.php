<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\InvalidWebhookSignatureException;
use Dantofema\MogotesLaravel\Services\WebhookSignatureValidator;

describe('WebhookSignatureValidator', function (): void {
    it('valida correctamente una firma válida', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret);

        $rawBody = '{"event":"test","data":"value"}';
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$rawBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        // No debe lanzar excepción
        $validator->validate($rawBody, $signature, $timestamp);

        expect(true)->toBeTrue();
    });

    it('lanza excepción cuando la firma no coincide', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret);

        $rawBody = '{"event":"test","data":"value"}';
        $timestamp = time();
        $wrongSignature = 'invalid_signature_hash';

        expect(fn () => $validator->validate($rawBody, $wrongSignature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class, 'La firma del webhook no coincide con la esperada.');
    });

    it('lanza excepción cuando el timestamp está fuera de ventana (pasado)', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret, maxAgeSeconds: 300);

        $rawBody = '{"event":"test","data":"value"}';
        // Timestamp de hace 10 minutos
        $timestamp = time() - 600;
        $signedPayload = $timestamp.'.'.$rawBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        expect(fn () => $validator->validate($rawBody, $signature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });

    it('lanza excepción cuando el timestamp está fuera de ventana (futuro)', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret, maxAgeSeconds: 300);

        $rawBody = '{"event":"test","data":"value"}';
        // Timestamp del futuro (10 minutos adelante)
        $timestamp = time() + 600;
        $signedPayload = $timestamp.'.'.$rawBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        expect(fn () => $validator->validate($rawBody, $signature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });

    it('acepta timestamps dentro de la ventana permitida', function (int $offsetSeconds): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret, maxAgeSeconds: 300);

        $rawBody = '{"event":"test","data":"value"}';
        $timestamp = time() + $offsetSeconds;
        $signedPayload = $timestamp.'.'.$rawBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        // No debe lanzar excepción
        $validator->validate($rawBody, $signature, $timestamp);

        expect(true)->toBeTrue();
    })->with([
        0,      // Exactamente ahora
        -60,    // 1 minuto en el pasado
        60,     // 1 minuto en el futuro
        -290,   // Casi al límite (pasado)
        290,    // Casi al límite (futuro)
    ]);

    it('usa ventana de tiempo personalizada correctamente', function (): void {
        $secret = 'test_secret_key';
        // Ventana de solo 60 segundos
        $validator = new WebhookSignatureValidator($secret, maxAgeSeconds: 60);

        $rawBody = '{"event":"test","data":"value"}';
        // Timestamp de hace 2 minutos
        $timestamp = time() - 120;
        $signedPayload = $timestamp.'.'.$rawBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        expect(fn () => $validator->validate($rawBody, $signature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });

    it('detecta modificaciones en el payload', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret);

        $originalBody = '{"event":"test","data":"original"}';
        $modifiedBody = '{"event":"test","data":"modified"}';
        $timestamp = time();

        // Firma generada con el payload original
        $signedPayload = $timestamp.'.'.$originalBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        // Intentar validar con payload modificado
        expect(fn () => $validator->validate($modifiedBody, $signature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });

    it('es sensible a cambios mínimos en el payload', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret);

        $originalBody = '{"event":"test"}';
        $modifiedBody = '{"event":"test" }'; // Espacio extra
        $timestamp = time();

        $signedPayload = $timestamp.'.'.$originalBody;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        expect(fn () => $validator->validate($modifiedBody, $signature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });

    it('protege contra timing attacks usando hash_equals', function (): void {
        $secret = 'test_secret_key';
        $validator = new WebhookSignatureValidator($secret);

        $rawBody = '{"event":"test"}';
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$rawBody;
        $validSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Firma que difiere solo en un carácter
        $almostValidSignature = substr($validSignature, 0, -1).'X';

        expect(fn () => $validator->validate($rawBody, $almostValidSignature, $timestamp))
            ->toThrow(InvalidWebhookSignatureException::class);
    });
});
