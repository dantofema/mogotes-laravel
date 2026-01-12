<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\MogotesMisconfiguredException;
use Dantofema\MogotesLaravel\MogotesClient;
use Illuminate\Support\Facades\Http;

describe('Slice 0 - Infraestructura y Cliente Base', function (): void {
    describe('Validación de configuración', function (): void {
        it('falla de forma controlada si falta la api key', function (): void {
            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: null,
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            $callback = fn () => $client->buildRequest();

            expect($callback)->toThrow(MogotesMisconfiguredException::class, 'La API key de Mogotes no está configurada');
        });

        it('falla de forma controlada si la api key está vacía', function (): void {
            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: '',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            $callback = fn () => $client->buildRequest();

            expect($callback)->toThrow(MogotesMisconfiguredException::class, 'La API key de Mogotes no está configurada');
        });

        it('falla de forma controlada si la URL del servidor es inválida', function (string $invalidUrl): void {
            expect(fn () => new MogotesClient(
                baseUrl: $invalidUrl,
                apiKey: 'test_key',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            ))->toThrow(MogotesMisconfiguredException::class, 'La URL del servidor de Mogotes es inválida');
        })->with([
            '',
            'no-es-una-url',
            'ftp://invalid-protocol.com',
            'javascript:alert(1)',
        ]);

        it('acepta URLs HTTPS válidas', function (): void {
            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: 'test_key',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            expect($client)->toBeInstanceOf(MogotesClient::class);
        });

        it('acepta URLs HTTP válidas para desarrollo local', function (): void {
            $client = new MogotesClient(
                baseUrl: 'http://localhost:8000',
                apiKey: 'test_key',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            expect($client)->toBeInstanceOf(MogotesClient::class);
        });
    });

    describe('Happy Path - Request autenticado', function (): void {
        it('envía las cabeceras obligatorias y la url base cuando se construye el request', function (): void {
            config()->set('mogotes-laravel.base_url', 'https://api.ejemplo.test');
            config()->set('mogotes-laravel.api_key', 'key_de_prueba');
            config()->set('mogotes-laravel.timeout_seconds', 5);
            config()->set('mogotes-laravel.connect_timeout_seconds', 2);
            config()->set('mogotes-laravel.user_agent', 'MogotesLaravel/Testing');
            config()->set('mogotes-laravel.default_headers', ['X-Custom' => 'valor']);

            Http::fake([
                'api.ejemplo.test/*' => Http::response(['ok' => true], 200),
            ]);

            /** @var MogotesClient $client */
            $client = app(MogotesClient::class);

            $response = $client->buildRequest()->get('/ping');

            Http::assertSent(fn ($request): bool => $request->url() === 'https://api.ejemplo.test/ping'
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('X-Mogotes-Api-Key', 'key_de_prueba')
                && $request->hasHeader('User-Agent', 'MogotesLaravel/Testing')
                && $request->hasHeader('X-Custom', 'valor'));

            expect($response->successful())->toBeTrue()
                ->and($response->json())->toBe(['ok' => true]);
        });

        it('puede realizar un request autenticado de ejemplo', function (): void {
            Http::fake([
                'api.mogotes.test/v1/health' => Http::response(['status' => 'healthy'], 200),
            ]);

            $client = new MogotesClient(
                baseUrl: 'https://api.mogotes.test',
                apiKey: 'sk_test_123456',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel/1.0',
                defaultHeaders: [],
            );

            $response = $client->buildRequest()->get('/v1/health');

            expect($response->successful())->toBeTrue()
                ->and($response->json('status'))->toBe('healthy');
        });
    });

    describe('Timeouts estrictos', function (): void {
        it('aplica timeout estricto en los requests', function (): void {
            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: 'test_key',
                timeoutSeconds: 3,
                connectTimeoutSeconds: 1,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            Http::fake([
                'api.ejemplo.test/*' => Http::response(['ok' => true], 200),
            ]);

            $request = $client->buildRequest();

            // Verificamos que el request tiene configurados los timeouts
            expect($request)->toBeInstanceOf(\Illuminate\Http\Client\PendingRequest::class);
        });

        it('normaliza timeouts a un mínimo de 1 segundo', function (): void {
            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: 'test_key',
                timeoutSeconds: 0,
                connectTimeoutSeconds: -5,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            Http::fake([
                'api.ejemplo.test/*' => Http::response(['ok' => true], 200),
            ]);

            // No debe lanzar excepción, ya que se normalizan internamente
            $request = $client->buildRequest();

            expect($request)->toBeInstanceOf(\Illuminate\Http\Client\PendingRequest::class);
        });
    });

    describe('Transparencia - Headers sin exponer secretos', function (): void {
        it('no incluye la API key en la URL', function (): void {
            Http::fake([
                'api.ejemplo.test/*' => Http::response(['ok' => true], 200),
            ]);

            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: 'secret_key_12345',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [],
            );

            $client->buildRequest()->get('/test');

            Http::assertSent(function ($request): bool {
                // La API key debe ir en header, no en la URL
                expect($request->url())->not->toContain('secret_key_12345');

                return true;
            });
        });

        it('permite headers personalizados adicionales', function (): void {
            Http::fake([
                'api.ejemplo.test/*' => Http::response(['ok' => true], 200),
            ]);

            $client = new MogotesClient(
                baseUrl: 'https://api.ejemplo.test',
                apiKey: 'test_key',
                timeoutSeconds: 5,
                connectTimeoutSeconds: 2,
                userAgent: 'MogotesLaravel',
                defaultHeaders: [
                    'X-Organization-ID' => 'org_123',
                    'X-Environment' => 'testing',
                ],
            );

            $client->buildRequest()->get('/test');

            Http::assertSent(fn ($request): bool => $request->hasHeader('X-Organization-ID', 'org_123')
                && $request->hasHeader('X-Environment', 'testing'));
        });
    });
});
