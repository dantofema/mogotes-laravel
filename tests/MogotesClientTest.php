<?php

declare(strict_types=1);

use Dantofema\MogotesLaravel\Exceptions\MogotesMisconfiguredException;
use Dantofema\MogotesLaravel\MogotesClient;
use Illuminate\Support\Facades\Http;

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

    $client->buildRequest()->get('/ping');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.ejemplo.test/ping'
        && $request->hasHeader('Accept', 'application/json')
        && $request->hasHeader('X-Mogotes-Api-Key', 'key_de_prueba')
        && $request->hasHeader('User-Agent', 'MogotesLaravel/Testing')
        && $request->hasHeader('X-Custom', 'valor'));
});
