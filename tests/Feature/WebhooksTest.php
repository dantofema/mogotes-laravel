<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Events\WebhookReceived;
use Illuminate\Support\Facades\Event;

describe('Slice 004 - Webhooks', function (): void {
    beforeEach(function (): void {
        config()->set('mogotes-laravel.webhooks.secret', 'test_webhook_secret');
        config()->set('mogotes-laravel.webhooks.register_route', true);
        config()->set('mogotes-laravel.webhooks.path', '/mogotes/webhook');
    });

    describe('Happy paths', function (): void {
        it('recibe y procesa un webhook válido correctamente', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'provider' => 'mogotes',
                'original_id' => 'evt_123',
                'mogotes_id' => 'mogotes_evt_456',
                'timestamp' => time(),
                'data' => [
                    'notification_id' => 'notif_789',
                    'to' => 'user@example.com',
                ],
            ];

            $rawBody = json_encode($payload);
            $timestamp = time();
            $signedPayload = $timestamp.'.'.$rawBody;
            $signature = hash_hmac('sha256', $signedPayload, 'test_webhook_secret');

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
                'Mogotes-Event' => 'email.delivered',
            ]);

            $response->assertOk()
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Webhook recibido correctamente',
                ]);

            Event::assertDispatched(WebhookReceived::class, fn ($event): bool => $event->payload === $payload
                && $event->signature === $signature
                && $event->timestamp === $timestamp);
        });

        it('procesa webhooks con diferentes tipos de eventos', function (string $eventType): void {
            Event::fake();

            $payload = [
                'event' => $eventType,
                'provider' => 'mogotes',
                'original_id' => 'evt_'.uniqid(),
                'mogotes_id' => 'mogotes_evt_'.uniqid(),
                'timestamp' => time(),
                'data' => ['test' => 'data'],
            ];

            $rawBody = json_encode($payload);
            $timestamp = time();
            $signedPayload = $timestamp.'.'.$rawBody;
            $signature = hash_hmac('sha256', $signedPayload, 'test_webhook_secret');

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
                'Mogotes-Event' => $eventType,
            ]);

            $response->assertOk();

            Event::assertDispatched(WebhookReceived::class);
        })->with([
            'email.delivered',
            'email.bounced',
            'email.complained',
            'whatsapp.delivered',
            'whatsapp.read',
            'user.registered',
        ]);
    });

    describe('Failure paths - Firma inválida', function (): void {
        it('rechaza webhook sin header Mogotes-Signature', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Timestamp' => (string) time(),
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'error' => [
                        'code' => 'invalid_signature',
                        'message' => 'El webhook no contiene la firma requerida en el header Mogotes-Signature.',
                    ],
                ]);

            Event::assertNotDispatched(WebhookReceived::class);
        });

        it('rechaza webhook sin header Mogotes-Timestamp', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => 'fake_signature',
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'error' => [
                        'code' => 'invalid_signature',
                        'message' => 'El webhook no contiene el timestamp requerido en el header Mogotes-Timestamp.',
                    ],
                ]);

            Event::assertNotDispatched(WebhookReceived::class);
        });

        it('rechaza webhook con firma incorrecta', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => 'invalid_signature',
                'Mogotes-Timestamp' => (string) time(),
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'error' => [
                        'code' => 'invalid_signature',
                        'message' => 'La firma del webhook no coincide con la esperada.',
                    ],
                ]);

            Event::assertNotDispatched(WebhookReceived::class);
        });

        it('rechaza webhook con timestamp fuera de ventana', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $rawBody = json_encode($payload);
            // Timestamp de hace 10 minutos (fuera de la ventana de 5 minutos)
            $timestamp = time() - 600;
            $signedPayload = $timestamp.'.'.$rawBody;
            $signature = hash_hmac('sha256', $signedPayload, 'test_webhook_secret');

            $response = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
            ]);

            $response->assertStatus(403)
                ->assertJsonPath('error.code', 'invalid_signature');

            Event::assertNotDispatched(WebhookReceived::class);
        });

        it('rechaza webhook cuando el payload fue modificado', function (): void {
            Event::fake();

            $originalPayload = [
                'event' => 'email.delivered',
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $rawBody = json_encode($originalPayload);
            $timestamp = time();
            $signedPayload = $timestamp.'.'.$rawBody;
            $signature = hash_hmac('sha256', $signedPayload, 'test_webhook_secret');

            // Modificar el payload después de generar la firma
            $modifiedPayload = [
                'event' => 'email.bounced', // Cambiado
                'mogotes_id' => 'test_123',
                'timestamp' => time(),
            ];

            $response = $this->postJson('/mogotes/webhook', $modifiedPayload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'error' => [
                        'code' => 'invalid_signature',
                        'message' => 'La firma del webhook no coincide con la esperada.',
                    ],
                ]);

            Event::assertNotDispatched(WebhookReceived::class);
        });
    });

    describe('Configuración de rutas', function (): void {
        it('registra la ruta por defecto cuando register_route es true', function (): void {
            $routes = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri());

            expect($routes->contains('mogotes/webhook'))->toBeTrue();
        });

        it('la ruta está nombrada correctamente', function (): void {
            $route = app('router')->getRoutes()->getByName('mogotes.webhook');

            expect($route)->not->toBeNull()
                ->and($route->uri())->toBe('mogotes/webhook')
                ->and($route->methods())->toContain('POST');
        });
    });

    describe('Idempotencia', function (): void {
        it('puede procesar el mismo evento múltiples veces (idempotencia del lado del cliente)', function (): void {
            Event::fake();

            $payload = [
                'event' => 'email.delivered',
                'provider' => 'mogotes',
                'original_id' => 'evt_same_123',
                'mogotes_id' => 'mogotes_evt_same_456',
                'timestamp' => time(),
                'data' => ['notification_id' => 'notif_789'],
            ];

            $rawBody = json_encode($payload);
            $timestamp = time();
            $signedPayload = $timestamp.'.'.$rawBody;
            $signature = hash_hmac('sha256', $signedPayload, 'test_webhook_secret');

            // Primera llamada
            $response1 = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
            ]);

            $response1->assertOk();

            // Segunda llamada con el mismo payload (simulando retry de Mogotes)
            $response2 = $this->postJson('/mogotes/webhook', $payload, [
                'Mogotes-Signature' => $signature,
                'Mogotes-Timestamp' => (string) $timestamp,
            ]);

            $response2->assertOk();

            // Ambos eventos deben ser disparados
            // La lógica de idempotencia debe ser manejada por el listener
            Event::assertDispatched(WebhookReceived::class, 2);
        });
    });
});
