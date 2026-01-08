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
