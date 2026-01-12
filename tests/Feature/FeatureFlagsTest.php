<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\Facades\Mogotes;
use Illuminate\Support\Facades\Http;

describe('Slice 001 - Feature Flags', function (): void {
    beforeEach(function (): void {
        config()->set('mogotes-laravel.base_url', 'https://api.mogotes.test');
        config()->set('mogotes-laravel.api_key', 'test_api_key');
    });

    describe('Happy paths', function (): void {
        it('puede verificar un flag activo sin scope', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [
                        'new-ui' => true,
                        'beta-feature' => false,
                    ],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('new-ui'))->toBeTrue();
            expect(Mogotes::feature()->IsActive('beta-feature'))->toBeFalse();

            Http::assertSent(function ($request): bool {
                return $request->url() === 'https://api.mogotes.test/v1/feature-flags'
                    && $request->hasHeader('X-API-KEY', 'test_api_key')
                    && $request->hasHeader('Accept', 'application/json');
            });
        });

        it('puede verificar un flag con scope_id por query param', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags?scope_id=user_123' => Http::response([
                    'flags' => [
                        'new-ui' => true,
                    ],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('new-ui', 'user_123'))->toBeTrue();

            Http::assertSent(function ($request): bool {
                return str_contains($request->url(), 'scope_id=user_123');
            });
        });

        it('retorna false cuando un flag no existe en la respuesta', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [
                        'existing-flag' => true,
                    ],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('non-existent-flag'))->toBeFalse();
        });

        it('retorna false cuando un flag es null', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [
                        'null-flag' => null,
                    ],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('null-flag'))->toBeFalse();
        });

        it('retorna false cuando flags está vacío', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('any-flag'))->toBeFalse();
        });

        it('interpreta valores no-booleanos como true', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [
                        'object-flag' => ['enabled' => true],
                        'array-flag' => [1, 2, 3],
                        'string-flag' => 'enabled',
                    ],
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('object-flag'))->toBeTrue();
            expect(Mogotes::feature()->IsActive('array-flag'))->toBeTrue();
            expect(Mogotes::feature()->IsActive('string-flag'))->toBeTrue();
        });
    });

    describe('Failure paths', function (): void {
        it('lanza MogotesUnauthorizedException cuando recibe 401', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'error' => [
                        'code' => 'unauthorized',
                        'message' => 'API key inválida',
                    ],
                ], 401),
            ]);

            expect(fn () => Mogotes::feature()->IsActive('any-flag'))
                ->toThrow(MogotesUnauthorizedException::class, 'API key inválida');
        });

        it('lanza MogotesApiException cuando recibe error 500', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'error' => [
                        'code' => 'internal_error',
                        'message' => 'Error interno del servidor',
                    ],
                ], 500),
            ]);

            expect(fn () => Mogotes::feature()->IsActive('any-flag'))
                ->toThrow(MogotesApiException::class);
        });

        it('lanza MogotesConnectionException cuando hay error de red', function (): void {
            Http::fake(function (): void {
                throw new Exception('Network error');
            });

            expect(fn () => Mogotes::feature()->IsActive('any-flag'))
                ->toThrow(MogotesConnectionException::class);
        });

        it('retorna false cuando la respuesta no tiene estructura esperada', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'data' => 'unexpected',
                ], 200),
            ]);

            expect(Mogotes::feature()->IsActive('any-flag'))->toBeFalse();
        });
    });

    describe('Headers correctos', function (): void {
        it('envía X-API-KEY según el VSF', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [],
                ], 200),
            ]);

            Mogotes::feature()->IsActive('test-flag');

            Http::assertSent(function ($request): bool {
                return $request->hasHeader('X-API-KEY', 'test_api_key');
            });
        });

        it('envía Accept application/json', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [],
                ], 200),
            ]);

            Mogotes::feature()->IsActive('test-flag');

            Http::assertSent(function ($request): bool {
                return $request->hasHeader('Accept', 'application/json');
            });
        });

        it('codifica correctamente el scope_id en la URL', function (): void {
            Http::fake([
                'api.mogotes.test/*' => Http::response([
                    'flags' => [],
                ], 200),
            ]);

            Mogotes::feature()->IsActive('test-flag', 'user@example.com');

            Http::assertSent(function ($request): bool {
                return str_contains($request->url(), 'scope_id='.urlencode('user@example.com'));
            });
        });
    });

    describe('Múltiples llamadas', function (): void {
        it('realiza una petición HTTP por cada llamada a IsActive', function (): void {
            Http::fake([
                'api.mogotes.test/v1/feature-flags' => Http::response([
                    'flags' => [
                        'flag-1' => true,
                        'flag-2' => false,
                    ],
                ], 200),
            ]);

            Mogotes::feature()->IsActive('flag-1');
            Mogotes::feature()->IsActive('flag-2');

            Http::assertSentCount(2);
        });
    });
});
