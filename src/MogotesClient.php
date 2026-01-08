<?php

namespace Dantofema\MogotesLaravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MogotesClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey,
    ) {}

    public function buildRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'X-Mogotes-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'User-Agent' => 'MogotesLaravel/1.0',
            ])
            ->timeout(2);
    }
}
