<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Services;

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\MogotesClient;
use Exception;

final readonly class FeatureFlagsService
{
    public function __construct(
        private MogotesClient $client
    ) {}

    /**
     * Determina si un feature flag está activo.
     *
     * @param  string  $name  El nombre del feature flag.
     * @param  string|null  $scopeId  El identificador de scope (opcional).
     * @return bool True si el flag está activo, false en caso contrario o si no existe.
     */
    public function IsActive(string $name, ?string $scopeId = null): bool
    {
        try {
            $request = $this->client->buildRequest();

            $url = '/v1/feature-flags';

            if ($scopeId !== null) {
                $url .= '?scope_id='.urlencode($scopeId);
            }

            $response = $request->get($url);

            if ($response->status() === 401) {
                throw MogotesUnauthorizedException::fromResponse($response);
            }

            if ($response->failed()) {
                throw MogotesApiException::fromResponse($response);
            }

            /** @var array<string, mixed> $flags */
            $flags = $response->json('flags', []);

            $value = $flags[$name] ?? null;

            // Si el valor es null o no existe, retornar false
            if ($value === null) {
                return false;
            }

            // Si es booleano, retornar directamente
            if (is_bool($value)) {
                return $value;
            }

            // Para cualquier otro tipo de valor (object, array, string), interpretar como true
            return true;

        } catch (MogotesUnauthorizedException|MogotesApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw MogotesConnectionException::unreachable($e->getMessage());
        }
    }
}
