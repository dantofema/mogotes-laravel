<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\Facades\Mogotes;
use Illuminate\Support\Facades\Http;

describe('Slice 003 - Logs', function (): void {
    beforeEach(function (): void {
        config()->set('mogotes-laravel.base_url', 'https://api.mogotes.test');
        config()->set('mogotes-laravel.api_key', 'test_api_key');
    });

    describe('Happy paths - Ingesta (POST)', function (): void {
        it('puede enviar un log con nivel info (201 created)', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_123',
                    'level' => 'info',
                    'message' => 'Order processed',
                    'context' => ['order_id' => 123],
                ], 201),
            ]);

            $result = Mogotes::log()->info('Order processed', ['order_id' => 123]);

            expect($result)->toHaveKeys(['id', 'level', 'message'])
                ->and($result['id'])->toBe('log_123')
                ->and($result['level'])->toBe('info');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $request->url() === 'https://api.mogotes.test/v1/logs'
                    && $request->hasHeader('X-API-KEY', 'test_api_key')
                    && $body['level'] === 'info'
                    && $body['message'] === 'Order processed'
                    && $body['context']['order_id'] === 123;
            });
        });

        it('puede enviar un log con nivel error', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_error_456',
                    'level' => 'error',
                    'message' => 'Payment provider failed',
                    'context' => ['provider' => 'stripe'],
                ], 201),
            ]);

            $result = Mogotes::log()->error('Payment provider failed', ['provider' => 'stripe']);

            expect($result)->toHaveKey('level')
                ->and($result['level'])->toBe('error');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $body['level'] === 'error'
                    && $body['message'] === 'Payment provider failed';
            });
        });

        it('puede enviar un log con nivel warning', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_warn_789',
                    'level' => 'warning',
                    'message' => 'Slow query detected',
                ], 201),
            ]);

            $result = Mogotes::log()->warning('Slow query detected', ['duration' => 5.2]);

            expect($result)->toHaveKey('level')
                ->and($result['level'])->toBe('warning');
        });

        it('puede enviar un log con nivel debug', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_debug_999',
                    'level' => 'debug',
                    'message' => 'Cache hit',
                ], 201),
            ]);

            $result = Mogotes::log()->debug('Cache hit', ['key' => 'user:123']);

            expect($result)->toHaveKey('level')
                ->and($result['level'])->toBe('debug');
        });

        it('puede enviar un log con nivel personalizado usando log()', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_custom_111',
                    'level' => 'critical',
                    'message' => 'System failure',
                ], 201),
            ]);

            $result = Mogotes::log()->log('critical', 'System failure', ['system' => 'payments']);

            expect($result)->toHaveKey('level')
                ->and($result['level'])->toBe('critical');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return $body['level'] === 'critical';
            });
        });

        it('envía context vacío si no se provee', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_222',
                    'level' => 'info',
                    'message' => 'Simple message',
                ], 201),
            ]);

            Mogotes::log()->info('Simple message');

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return isset($body['context']) && $body['context'] === [];
            });
        });
    });

    describe('Happy paths - Consulta (GET)', function (): void {
        it('puede listar logs sin filtros (200)', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'data' => [
                        ['id' => 'log_1', 'level' => 'info', 'message' => 'Test 1'],
                        ['id' => 'log_2', 'level' => 'error', 'message' => 'Test 2'],
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => 15,
                        'total' => 2,
                    ],
                ], 200),
            ]);

            $result = Mogotes::log()->list();

            expect($result)->toHaveKey('data')
                ->and($result['data'])->toHaveCount(2)
                ->and($result['meta']['total'])->toBe(2);

            Http::assertSent(fn($request): bool => $request->url() === 'https://api.mogotes.test/v1/logs'
                && $request->method() === 'GET'
                && $request->hasHeader('X-API-KEY', 'test_api_key'));
        });

        it('puede listar logs con filtro de level', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs*' => Http::response([
                    'data' => [
                        ['id' => 'log_error_1', 'level' => 'error', 'message' => 'Error 1'],
                    ],
                ], 200),
            ]);

            $result = Mogotes::log()->list(['level' => 'error']);

            expect($result['data'])->toHaveCount(1);

            Http::assertSent(fn($request): bool => str_contains((string) $request->url(), 'level=error'));
        });

        it('puede listar logs con múltiples filtros', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs*' => Http::response([
                    'data' => [],
                ], 200),
            ]);

            Mogotes::log()->list([
                'level' => 'info',
                'type' => 'application',
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-12',
                'per_page' => 25,
            ]);

            Http::assertSent(function ($request): bool {
                $url = $request->url();

                return str_contains($url, 'level=info')
                    && str_contains($url, 'type=application')
                    && str_contains($url, 'from_date=2026-01-01')
                    && str_contains($url, 'to_date=2026-01-12')
                    && str_contains($url, 'per_page=25');
            });
        });

        it('es tolerante al formato de paginación (data/meta/links)', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'data' => [
                        ['id' => 'log_1', 'message' => 'Test'],
                    ],
                    'links' => [
                        'next' => 'https://api.mogotes.test/v1/logs?page=2',
                    ],
                ], 200),
            ]);

            $result = Mogotes::log()->list();

            expect($result)->toHaveKey('data')
                ->and($result['data'])->toHaveCount(1);
        });
    });

    describe('Failure paths', function (): void {
        it('lanza MogotesUnauthorizedException cuando recibe 401 en POST', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'API key inválida',
                    ],
                ], 401),
            ]);

            expect(fn () => Mogotes::log()->info('Test message'))
                ->toThrow(MogotesUnauthorizedException::class, 'API key inválida');
        });

        it('lanza MogotesUnauthorizedException cuando recibe 401 en GET', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'API key inválida',
                    ],
                ], 401),
            ]);

            expect(fn () => Mogotes::log()->list())
                ->toThrow(MogotesUnauthorizedException::class, 'API key inválida');
        });

        it('lanza MogotesApiException cuando recibe error 500 en POST', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'error' => [
                        'code' => 'internal_error',
                        'message' => 'Error interno del servidor',
                    ],
                ], 500),
            ]);

            expect(fn () => Mogotes::log()->error('Test error'))
                ->toThrow(MogotesApiException::class);
        });

        it('lanza MogotesApiException cuando recibe error 500 en GET', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'error' => [
                        'code' => 'internal_error',
                        'message' => 'Error interno del servidor',
                    ],
                ], 500),
            ]);

            expect(fn () => Mogotes::log()->list())
                ->toThrow(MogotesApiException::class);
        });

        it('lanza MogotesConnectionException cuando hay error de red en POST', function (): void {
            Http::fake(function (): void {
                throw new Exception('Network error');
            });

            expect(fn () => Mogotes::log()->info('Test message'))
                ->toThrow(MogotesConnectionException::class);
        });

        it('lanza MogotesConnectionException cuando hay error de red en GET', function (): void {
            Http::fake(function (): void {
                throw new Exception('Network error');
            });

            expect(fn () => Mogotes::log()->list())
                ->toThrow(MogotesConnectionException::class);
        });
    });

    describe('Validación del payload (POST)', function (): void {
        it('envía todos los campos requeridos en el payload', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_123',
                ], 201),
            ]);

            Mogotes::log()->info('Test message', ['user_id' => 456]);

            Http::assertSent(function ($request): bool {
                $body = $request->data();

                return isset($body['level'])
                    && isset($body['message'])
                    && isset($body['context'])
                    && $body['level'] === 'info'
                    && $body['message'] === 'Test message'
                    && $body['context']['user_id'] === 456;
            });
        });

        it('envía headers correctos según VSF', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_123',
                ], 201),
            ]);

            Mogotes::log()->info('Test message');

            Http::assertSent(fn($request): bool => $request->hasHeader('X-API-KEY', 'test_api_key')
                && $request->hasHeader('Accept', 'application/json'));
        });
    });

    describe('Edge cases - Sanitización de contexto', function (): void {
        it('puede enviar context con datos sensibles (servidor debe sanitizar)', function (): void {
            Http::fake([
                'api.mogotes.test/v1/logs' => Http::response([
                    'id' => 'log_sanitized',
                    'context' => [
                        'password' => '[FILTERED]',
                        'token' => '[FILTERED]',
                        'user_id' => 123,
                    ],
                ], 201),
            ]);

            $result = Mogotes::log()->info('User login attempt', [
                'password' => 'secret123',
                'token' => 'bearer_abc',
                'user_id' => 123,
            ]);

            // El SDK envía todo, servidor sanitiza
            expect($result['context']['password'])->toBe('[FILTERED]');
            expect($result['context']['token'])->toBe('[FILTERED]');
            expect($result['context']['user_id'])->toBe(123);
        });
    });
});
