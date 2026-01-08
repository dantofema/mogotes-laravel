<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    Http::fake();
});

it('resuelve flags desde Mogotes usando Feature::active', function (): void {
    config()->set('mogotes-laravel.base_url', 'https://api.ejemplo.test');
    config()->set('mogotes-laravel.api_key', 'key_de_prueba');
    config()->set('mogotes-laravel.feature_flags.cache_enabled', false);

    Http::fake([
        'https://api.ejemplo.test/api/v1/feature-flags' => Http::response([
            'flags' => [
                'new-ui' => true,
            ],
        ]),
    ]);

    expect(Feature::store('mogotes')->active('new-ui'))->toBeTrue();
    expect(Feature::store('mogotes')->active('flag-inexistente'))->toBeFalse();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://api.ejemplo.test/api/v1/feature-flags'
            && $request->hasHeader('X-API-KEY', 'key_de_prueba')
            && $request->hasHeader('X-SCOPE-ID');
    });
});

it('soporta scopes y envía X-SCOPE-ID', function (): void {
    config()->set('mogotes-laravel.base_url', 'https://api.ejemplo.test');
    config()->set('mogotes-laravel.api_key', 'key_de_prueba');
    config()->set('mogotes-laravel.feature_flags.cache_enabled', false);

    Http::fake([
        'https://api.ejemplo.test/api/v1/feature-flags' => function ($request) {
            $scopeId = $request->header('X-SCOPE-ID')[0] ?? null;

            return Http::response([
                'flags' => [
                    'new-ui' => $scopeId === 'user_1',
                ],
            ], 200);
        },
    ]);

    expect(Feature::store('mogotes')->for('user_1')->active('new-ui'))->toBeTrue();
    expect(Feature::store('mogotes')->for('user_2')->active('new-ui'))->toBeFalse();

    Http::assertSentCount(2);
});

it('degrada de forma segura ante error remoto', function (): void {
    config()->set('mogotes-laravel.base_url', 'https://api.ejemplo.test');
    config()->set('mogotes-laravel.api_key', 'key_de_prueba');
    config()->set('mogotes-laravel.feature_flags.cache_enabled', false);

    Http::fake([
        'https://api.ejemplo.test/api/v1/feature-flags' => Http::response(null, 500),
    ]);

    expect(Feature::store('mogotes')->active('new-ui'))->toBeFalse();
});

it('cachea por scope cuando está habilitado', function (): void {
    Cache::flush();

    config()->set('mogotes-laravel.base_url', 'https://api.ejemplo.test');
    config()->set('mogotes-laravel.api_key', 'key_de_prueba');
    config()->set('mogotes-laravel.feature_flags.cache_enabled', true);
    config()->set('mogotes-laravel.feature_flags.ttl_seconds', 300);

    Http::fake([
        'https://api.ejemplo.test/api/v1/feature-flags' => Http::response([
            'flags' => [
                'new-ui' => true,
            ],
        ], 200),
    ]);

    expect(Feature::store('mogotes')->active('new-ui'))->toBeTrue();
    expect(Feature::store('mogotes')->active('new-ui'))->toBeTrue();

    Http::assertSentCount(1);
});
