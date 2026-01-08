<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel;

use Dantofema\MogotesLaravel\Exceptions\MogotesMisconfiguredException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final readonly class MogotesClient
{
    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private string $baseUrl,
        private ?string $apiKey,
        private int $timeoutSeconds,
        private int $connectTimeoutSeconds,
        private string $userAgent,
        private array $defaultHeaders = [],
    ) {}

    public function buildRequest(): PendingRequest
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw MogotesMisconfiguredException::missingApiKey();
        }

        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withHeaders($this->buildHeaders())
            ->connectTimeout($this->normalizedConnectTimeoutSeconds())
            ->timeout($this->normalizedTimeoutSeconds());
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            ...$this->defaultHeaders,
            'X-Mogotes-Api-Key' => (string) $this->apiKey,
            'User-Agent' => $this->userAgent,
        ];
    }

    private function normalizedTimeoutSeconds(): int
    {
        return max(1, $this->timeoutSeconds);
    }

    private function normalizedConnectTimeoutSeconds(): int
    {
        return max(1, $this->connectTimeoutSeconds);
    }
}
