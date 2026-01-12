<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel;

use Dantofema\MogotesLaravel\Services\FeatureFlagsService;

class MogotesLaravel
{
    private ?FeatureFlagsService $featureFlagsService = null;

    public function __construct(
        protected MogotesClient $client
    ) {}

    /**
     * Obtiene el servicio de Feature Flags.
     */
    public function feature(): FeatureFlagsService
    {
        if ($this->featureFlagsService === null) {
            $this->featureFlagsService = new FeatureFlagsService($this->client);
        }

        return $this->featureFlagsService;
    }

    /**
     * Envía una notificación utilizando una plantilla gestionada en Mogotes.
     *
     * @param  string  $template  El identificador o key de la plantilla.
     * @param  string  $channel  El canal de envío (email, whatsapp, sms, push).
     * @param  string  $to  El destinatario (email o teléfono).
     * @param  array<string, mixed>  $data  Variables para inyectar en la plantilla.
     * @return array<string, mixed> Respuesta de la API de Mogotes.
     */
    public function sendNotification(string $template, string $channel, string $to, array $data = []): array
    {
        // TODO: Implementar llamada a la API de Mogotes usando MogotesClient
        return [
            'status' => 'accepted',
            'channel' => $channel,
            'template_key' => $template,
            'to' => $to,
            'data' => $data,
        ];
    }
}
