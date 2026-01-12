<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel;

use Dantofema\MogotesLaravel\Services\FeatureFlagsService;
use Dantofema\MogotesLaravel\Services\LogsService;
use Dantofema\MogotesLaravel\Services\NotificationsService;

class MogotesLaravel
{
    private ?FeatureFlagsService $featureFlagsService = null;

    private ?NotificationsService $notificationsService = null;

    private ?LogsService $logsService = null;

    public function __construct(
        protected MogotesClient $client
    ) {}

    /**
     * Obtiene el servicio de Feature Flags.
     */
    public function feature(): FeatureFlagsService
    {
        if (! $this->featureFlagsService instanceof FeatureFlagsService) {
            $this->featureFlagsService = new FeatureFlagsService($this->client);
        }

        return $this->featureFlagsService;
    }

    /**
     * Obtiene el servicio de Logs.
     */
    public function log(): LogsService
    {
        if (! $this->logsService instanceof LogsService) {
            $this->logsService = new LogsService($this->client);
        }

        return $this->logsService;
    }

    /**
     * Envía una notificación por email usando una plantilla de Mogotes.
     *
     * @param  string  $template  El identificador de la plantilla.
     * @param  string  $to  El destinatario (email).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @param  string|null  $idempotencyKey  Clave de idempotencia (se genera automáticamente si es null).
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function email(string $template, string $to, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->notifications()->email($template, $to, $data, $idempotencyKey);
    }

    /**
     * Envía una notificación por WhatsApp usando una plantilla de Mogotes.
     *
     * @param  string  $template  El identificador de la plantilla.
     * @param  string  $to  El destinatario (teléfono).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @param  string|null  $idempotencyKey  Clave de idempotencia (se genera automáticamente si es null).
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function whatsapp(string $template, string $to, array $data = [], ?string $idempotencyKey = null): array
    {
        return $this->notifications()->whatsapp($template, $to, $data, $idempotencyKey);
    }

    /**
     * Obtiene el servicio de notificaciones.
     */
    public function notifications(): NotificationsService
    {
        if (! $this->notificationsService instanceof NotificationsService) {
            $this->notificationsService = new NotificationsService($this->client);
        }

        return $this->notificationsService;
    }

    /**
     * Envía una notificación utilizando una plantilla gestionada en Mogotes.
     *
     * @param  string  $template  El identificador o key de la plantilla.
     * @param  string  $channel  El canal de envío (email, whatsapp, sms, push).
     * @param  string  $to  El destinatario (email o teléfono).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     *
     * @deprecated Usar email() o whatsapp() en su lugar
     */
    public function sendNotification(string $template, string $channel, string $to, array $data = []): array
    {
        return $this->notifications()->send($channel, $template, $to, $data);
    }
}
