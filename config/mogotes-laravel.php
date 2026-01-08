<?php

// config for Dantofema/Mogotes
return [
    /**
     * La URL base del servidor de Mogotes.
     * Si no se especifica, se utilizará el valor por defecto de la plataforma central.
     */
    'base_url' => env('MOGOTES_SERVER_URL', 'https://api.mogotes.com'),

    /**
     * La API Key utilizada para autenticar las peticiones con el servidor.
     * Puedes obtener esta clave en el panel de control de Mogotes.
     */
    'api_key' => env('MOGOTES_API_KEY'),

    /**
     * Timeout total del request (en segundos).
     * Debe ser estricto para evitar bloquear la aplicación cliente ante caídas del servicio.
     */
    'timeout_seconds' => (int) env('MOGOTES_TIMEOUT_SECONDS', 5),

    /**
     * Timeout de conexión (en segundos).
     */
    'connect_timeout_seconds' => (int) env('MOGOTES_CONNECT_TIMEOUT_SECONDS', 2),

    /**
     * User-Agent a enviar en todas las peticiones.
     */
    'user_agent' => env('MOGOTES_USER_AGENT', 'MogotesLaravel'),

    /**
     * Cabeceras adicionales a incluir en todas las peticiones.
     *
     * @var array<string, string>
     */
    'default_headers' => [],

    /**
     * Configuración del driver de Feature Flags (Laravel Pennant).
     */
    'feature_flags' => [
        /**
         * TTL del caché (en segundos) para el fetch de flags por scope.
         */
        'ttl_seconds' => (int) env('MOGOTES_FEATURE_FLAGS_TTL_SECONDS', 300),

        /**
         * Determina si se habilita el caché de Feature Flags.
         */
        'cache_enabled' => (bool) env('MOGOTES_FEATURE_FLAGS_CACHE_ENABLED', true),
    ],

    /**
     * Configuración de la recepción de webhooks.
     */
    'webhooks' => [
        /*
         * Determina si se deben registrar automáticamente las rutas de webhooks.
         */
        'register_route' => env('MOGOTES_WEBHOOK_REGISTER_ROUTE', true),

        /*
         * El path donde se recibirán los eventos de Mogotes.
         */
        'path' => env('MOGOTES_WEBHOOK_PATH', '/mogotes/webhook'),

        /*
         * El secreto utilizado para validar la firma HMAC de los webhooks entrantes.
         */
        'secret' => env('MOGOTES_WEBHOOK_SECRET'),
    ],
];
