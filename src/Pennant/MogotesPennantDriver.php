<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Pennant;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Contracts\Driver;
use RuntimeException;

final readonly class MogotesPennantDriver implements Driver
{
    /**
     * @param  int  $ttlSeconds  segundos
     */
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private int $ttlSeconds = 300,
        private bool $cacheEnabled = true,
    ) {}

    public function define(string $feature, callable $resolver): void
    {
        // El driver de Mogotes delega la definición al servidor.
    }

    /**
     * @return array<string>
     */
    public function defined(): array
    {
        return array_keys($this->resolveAll(null));
    }

    /**
     * @param  array<string, array<int, mixed>>  $features
     * @return array<string, array<int, mixed>>
     */
    public function getAll(array $features): array
    {
        $results = [];

        foreach ($features as $feature => $scopes) {
            foreach ($scopes as $scope) {
                $results[$feature][] = $this->get($feature, $scope);
            }
        }

        return $results;
    }

    public function get(string $feature, mixed $scope): mixed
    {
        $scopeId = $this->resolveScopeId($scope);
        $allFlags = $this->resolveAll($scopeId);

        return $allFlags[$feature] ?? false;
    }

    public function set(string $feature, mixed $scope, mixed $value): void
    {
        throw new RuntimeException('El driver de Mogotes no soporta escritura local.');
    }

    public function setForAllScopes(string $feature, mixed $value): void
    {
        throw new RuntimeException('El driver de Mogotes no soporta escritura local.');
    }

    public function delete(string $feature, mixed $scope): void
    {
        // No implementado para este slice.
    }

    /**
     * @param  array<int, string>|null  $features
     */
    public function purge(?array $features): void
    {
        Cache::forget($this->cacheKey(null));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAll(?string $scopeId): array
    {
        $cacheKey = $this->cacheKey($scopeId);

        if (! $this->cacheEnabled || $this->ttlSeconds <= 0) {
            return $this->fetchFlags($scopeId);
        }

        return Cache::remember($cacheKey, $this->ttlSeconds, fn (): array => $this->fetchFlags($scopeId));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFlags(?string $scopeId): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-SCOPE-ID' => $scopeId,
            ])->get(rtrim($this->baseUrl, '/').'/api/v1/feature-flags');

            if ($response->failed()) {
                return [];
            }

            /** @var array<string, mixed> $flags */
            $flags = $response->json('flags', []);

            return $flags;
        } catch (Exception) {
            return [];
        }
    }

    private function resolveScopeId(mixed $scope): ?string
    {
        if ($scope === null) {
            return null;
        }

        if (is_string($scope) || is_int($scope)) {
            return (string) $scope;
        }

        if (is_object($scope) && method_exists($scope, 'getAuthIdentifier')) {
            /** @var mixed $id */
            $id = $scope->getAuthIdentifier();

            return is_scalar($id) ? (string) $id : null;
        }

        if (is_object($scope) && property_exists($scope, 'id')) {
            /** @var mixed $id */
            $id = $scope->id;

            return is_scalar($id) ? (string) $id : null;
        }

        if (is_object($scope) && method_exists($scope, '__toString')) {
            return (string) $scope;
        }

        return null;
    }

    private function cacheKey(?string $scopeId): string
    {
        return 'pennant:mogotes:'.($scopeId ?? 'global');
    }
}
