<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Services;

use Dantofema\MogotesLaravel\Exceptions\MogotesApiException;
use Dantofema\MogotesLaravel\Exceptions\MogotesConnectionException;
use Dantofema\MogotesLaravel\Exceptions\MogotesUnauthorizedException;
use Dantofema\MogotesLaravel\MogotesClient;
use Exception;

final readonly class LogsService
{
    public function __construct(
        private MogotesClient $client
    ) {}

    /**
     * Envía un log con nivel info a Mogotes.
     *
     * @param  string  $message  El mensaje del log.
     * @param  array<string, mixed>  $context  Contexto adicional.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function info(string $message, array $context = []): array
    {
        return $this->log('info', $message, $context);
    }

    /**
     * Envía un log con nivel error a Mogotes.
     *
     * @param  string  $message  El mensaje del log.
     * @param  array<string, mixed>  $context  Contexto adicional.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function error(string $message, array $context = []): array
    {
        return $this->log('error', $message, $context);
    }

    /**
     * Envía un log con nivel warning a Mogotes.
     *
     * @param  string  $message  El mensaje del log.
     * @param  array<string, mixed>  $context  Contexto adicional.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function warning(string $message, array $context = []): array
    {
        return $this->log('warning', $message, $context);
    }

    /**
     * Envía un log con nivel debug a Mogotes.
     *
     * @param  string  $message  El mensaje del log.
     * @param  array<string, mixed>  $context  Contexto adicional.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function debug(string $message, array $context = []): array
    {
        return $this->log('debug', $message, $context);
    }

    /**
     * Envía un log genérico a Mogotes.
     *
     * @param  string  $level  El nivel del log (info, error, warning, debug).
     * @param  string  $message  El mensaje del log.
     * @param  array<string, mixed>  $context  Contexto adicional.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function log(string $level, string $message, array $context = []): array
    {
        try {
            $payload = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ];

            $request = $this->client->buildRequest();

            $response = $request->post('/v1/logs', $payload);

            if ($response->status() === 401) {
                throw MogotesUnauthorizedException::fromResponse($response);
            }

            if ($response->failed()) {
                throw MogotesApiException::fromResponse($response);
            }

            /** @var array<string, mixed> $responseData */
            $responseData = $response->json();

            return $responseData;

        } catch (MogotesUnauthorizedException|MogotesApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw MogotesConnectionException::unreachable($e->getMessage());
        }
    }

    /**
     * Lista logs desde Mogotes con filtros opcionales.
     *
     * @param  array<string, mixed>  $filters  Filtros de consulta (level, type, from_date, to_date, per_page).
     * @return array<string, mixed> Respuesta de la API de Mogotes con logs paginados.
     */
    public function list(array $filters = []): array
    {
        try {
            $request = $this->client->buildRequest();

            $response = $request->get('/v1/logs', $filters);

            if ($response->status() === 401) {
                throw MogotesUnauthorizedException::fromResponse($response);
            }

            if ($response->failed()) {
                throw MogotesApiException::fromResponse($response);
            }

            /** @var array<string, mixed> $responseData */
            $responseData = $response->json();

            return $responseData;

        } catch (MogotesUnauthorizedException|MogotesApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw MogotesConnectionException::unreachable($e->getMessage());
        }
    }
}
