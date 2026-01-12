<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\Facades\Mogotes;
use Illuminate\Support\Facades\Http;

describe('Slice 002 - Notificaciones', function (): void {
    beforeEach(function (): void {
        config()->set('mogotes-laravel.base_url', 'https://api.mogotes.test');
        config()->set('mogotes-laravel.api_key', 'test_api_key');
    });

    describe('Happy paths - Email', function (): void {
        it('puede enviar una notificación por email con 201 created', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_123',
                    'status' => 'queued',
                    'channel' => 'email',
                    'template_key' => 'order_paid',
                    'to' => 'buyer@example.com',
                ], 201),
            ]);

            $result = Mogotes::email(
                template: 'order_paid',
                to: 'buyer@example.com',
                data: ['reference' => 'ABC-123']
            );

            expect($result)->toHaveKeys(['id', 'status', 'channel'])
                ->and($result['id'])->toBe('notif_123')
                ->and($result['status'])->toBe('queued');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $request->url() === 'https://api.mogotes.test/v1/notifications'
                    && $request->hasHeader('X-API-KEY', 'test_api_key')
                    && $body['channel'] === 'email'
                    && $body['template_key'] === 'order_paid'
                    && $body['to'] === 'buyer@example.com'
                    && isset($body['idempotency_key']);
            });
        });

        it('puede enviar una notificación por email con idempotency_key personalizada', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_456',
                    'status' => 'queued',
                ], 201),
            ]);

            $customKey = 'my-custom-key-123';

            Mogotes::email(
                template: 'order_paid',
                to: 'buyer@example.com',
                data: ['reference' => 'ABC-123'],
                idempotencyKey: $customKey
            );

            Http::assertSent(function ($request) use ($customKey): bool {
                $body = $request->data();

                return $body['idempotency_key'] === $customKey;
            });
        });

        it('genera idempotency_key automáticamente si no se provee', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_789',
                    'status' => 'queued',
                ], 201),
            ]);

            Mogotes::email(
                template: 'order_paid',
                to: 'buyer@example.com',
                data: ['reference' => 'ABC-123']
            );

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return isset($body['idempotency_key'])
                    && is_string($body['idempotency_key'])
                    && strlen($body['idempotency_key']) > 0;
            });
        });

        it('trata 200 (idempotencia hit) como éxito', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_123',
                    'status' => 'sent',
                    'channel' => 'email',
                ], 200),
            ]);

            $result = Mogotes::email(
                template: 'order_paid',
                to: 'buyer@example.com',
                data: ['reference' => 'ABC-123'],
                idempotencyKey: 'same-key-as-before'
            );

            expect($result)->toHaveKey('id')
                ->and($result['id'])->toBe('notif_123');
        });
    });

    describe('Happy paths - WhatsApp', function (): void {
        it('puede enviar una notificación por WhatsApp con 201 created', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_whatsapp_123',
                    'status' => 'queued',
                    'channel' => 'whatsapp',
                    'template_key' => 'order_paid',
                    'to' => '+54 9 11 2233-4455',
                ], 201),
            ]);

            $result = Mogotes::whatsapp(
                template: 'order_paid',
                to: '+54 9 11 2233-4455',
                data: ['reference' => 'ABC-123']
            );

            expect($result)->toHaveKeys(['id', 'status', 'channel'])
                ->and($result['channel'])->toBe('whatsapp');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $body['channel'] === 'whatsapp'
                    && $body['to'] === '+54 9 11 2233-4455';
            });
        });
    });

    describe('Failure paths', function (): void {
        it('lanza MogotesUnauthorizedException cuando recibe 401', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'API key inválida',
                    ],
                ], 401),
            ]);

            expect(fn () => Mogotes::email('order_paid', 'buyer@example.com'))
                ->toThrow(MogotesUnauthorizedException::class, 'API key inválida');
        });

        it('lanza MogotesApiException cuando recibe 422 validación', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'error' => [
                        'code' => 'validation_error',
                        'message' => 'El campo to es requerido',
                    ],
                ], 422),
            ]);

            expect(fn () => Mogotes::email('order_paid', ''))
                ->toThrow(MogotesApiException::class);
        });

        it('lanza MogotesConnectionException cuando hay error de red', function (): void {
            Http::fake(function (): void {
                throw new Exception('Network error');
            });

            expect(fn () => Mogotes::email('order_paid', 'buyer@example.com'))
                ->toThrow(MogotesConnectionException::class);
        });

        it('lanza MogotesApiException cuando recibe error 500', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'error' => [
                        'code' => 'internal_error',
                        'message' => 'Error interno del servidor',
                    ],
                ], 500),
            ]);

            expect(fn () => Mogotes::whatsapp('order_paid', '+54 9 11 2233-4455'))
                ->toThrow(MogotesApiException::class);
        });
    });

    describe('Validación del payload', function (): void {
        it('envía todos los campos requeridos en el payload', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_123',
                    'status' => 'queued',
                ], 201),
            ]);

            Mogotes::email(
                template: 'welcome_email',
                to: 'user@example.com',
                data: ['name' => 'John', 'code' => '12345']
            );

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return isset($body['channel'])
                    && isset($body['template_key'])
                    && isset($body['to'])
                    && isset($body['data'])
                    && isset($body['idempotency_key'])
                    && $body['template_key'] === 'welcome_email'
                    && $body['data']['name'] === 'John';
            });
        });

        it('envía data como array vacío si no se provee', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_123',
                    'status' => 'queued',
                ], 201),
            ]);

            Mogotes::email('simple_template', 'user@example.com');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return isset($body['data']) && $body['data'] === [];
            });
        });
    });

    describe('Servicio de notificaciones directo', function (): void {
        it('puede usar el servicio notifications() para enviar por cualquier canal', function (): void {
            Http::fake([
                'api.mogotes.test/v1/notifications' => Http::response([
                    'id' => 'notif_sms_123',
                    'status' => 'queued',
                    'channel' => 'sms',
                ], 201),
            ]);

            $result = Mogotes::notifications()->send(
                channel: 'sms',
                template: 'verification_code',
                to: '+54 9 11 1234-5678',
                data: ['code' => '123456']
            );

            expect($result)->toHaveKey('channel')
                ->and($result['channel'])->toBe('sms');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $body['channel'] === 'sms';
            });
        });
    });
});
