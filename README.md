# Mogotes Laravel

<p align="center">
<a href="https://packagist.org/packages/dantofema/mogotes-laravel"><img src="https://img.shields.io/packagist/v/dantofema/mogotes-laravel.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/dantofema/mogotes-laravel"><img src="https://img.shields.io/packagist/dt/dantofema/mogotes-laravel.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/dantofema/mogotes-laravel"><img src="https://img.shields.io/packagist/l/dantofema/mogotes-laravel.svg?style=flat-square" alt="License"></a>
</p>

Cliente Laravel para interactuar con los servicios de [Mogotes](https://mogotes.com) - Feature Flags, Notificaciones y Logs.

## Instalación

```bash
composer require dantofema/mogotes-laravel
```

Publica el archivo de configuración:

```bash
php artisan vendor:publish --tag="mogotes-laravel-config"
```

Configura las variables de entorno en tu `.env`:

```env
MOGOTES_API_KEY=tu_api_key_aqui
MOGOTES_SERVER_URL=https://api.mogotes.com
MOGOTES_WEBHOOK_SECRET=tu_webhook_secret
```

## Uso

### 📧 Notificaciones

Envía notificaciones por email o WhatsApp usando plantillas configuradas en Mogotes.

```php
use Dantofema\MogotesLaravel\Facades\Mogotes;

// Email
Mogotes::email(
    template: 'welcome-email',
    to: 'user@example.com',
    data: ['name' => 'Juan', 'code' => '12345']
);

// WhatsApp
Mogotes::whatsapp(
    template: 'order-confirmation',
    to: '+5491112345678',
    data: ['order_id' => '12345', 'total' => '$100']
);

// Notificación genérica (cualquier canal)
Mogotes::notifications()->send(
    channel: 'email',
    template: 'custom-template',
    to: 'recipient@example.com',
    data: ['key' => 'value'],
    idempotencyKey: 'unique-key-123' // Opcional
);
```

### 📊 Logs

Envía logs estructurados a Mogotes para centralizar el monitoreo de tu aplicación.

```php
use Dantofema\MogotesLaravel\Facades\Mogotes;

// Log de información
Mogotes::log()->info('Usuario creado', [
    'user_id' => 123,
    'email' => 'user@example.com'
]);

// Log de error
Mogotes::log()->error('Falló el pago', [
    'payment_id' => 456,
    'error' => 'Tarjeta rechazada'
]);

// Otros niveles disponibles
Mogotes::log()->warning('Advertencia', ['context' => 'value']);
Mogotes::log()->debug('Debug info', ['data' => [...]]);

// Listar logs con filtros
$logs = Mogotes::log()->list([
    'level' => 'error',
    'from_date' => '2024-01-01',
    'to_date' => '2024-01-31',
    'per_page' => 50
]);
```

### 🚩 Feature Flags

Controla funcionalidades de tu aplicación de forma dinámica sin redesplegar código.

```php
use Dantofema\MogotesLaravel\Facades\Mogotes;

// Verificar si un flag está activo
if (Mogotes::feature()->IsActive('nueva-funcionalidad')) {
    // Código para la nueva funcionalidad
}

// Con scope (por usuario, tenant, etc.)
if (Mogotes::feature()->IsActive('beta-feature', scopeId: 'user-123')) {
    // Funcionalidad beta para usuario específico
}
```

**Integración con Laravel Pennant:**

El paquete también registra automáticamente un driver de Pennant para usar Feature Flags nativamente:

```php
use Laravel\Pennant\Feature;

// Verificar flag
if (Feature::active('nueva-funcionalidad')) {
    // ...
}

// Con scope
Feature::for($user)->active('beta-feature');
```

### 🔐 Webhooks

Recibe eventos de Mogotes en tu aplicación de forma segura.

#### Configuración automática

El paquete registra automáticamente la ruta `/mogotes/webhook` (configurable en `.env`):

```env
MOGOTES_WEBHOOK_PATH=/mogotes/webhook
MOGOTES_WEBHOOK_REGISTER_ROUTE=true
```

#### Validación de firma

```php
use Dantofema\MogotesLaravel\Services\WebhookSignatureValidator;

$validator = new WebhookSignatureValidator(
    secret: config('mogotes-laravel.webhooks.secret')
);

try {
    $validator->validate(
        rawBody: $request->getContent(),
        signature: $request->header('Mogotes-Signature'),
        timestamp: (int) $request->header('Mogotes-Timestamp')
    );
    
    // Webhook válido, procesar evento
    $event = $request->json();
    
} catch (InvalidWebhookSignatureException $e) {
    // Firma inválida o timestamp expirado
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

#### Escuchar eventos

Crea listeners para los eventos de Mogotes:

```php
use Dantofema\MogotesLaravel\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function (WebhookReceived $event) {
    $payload = $event->payload;
    
    // Procesar evento según tipo
    match ($payload['event_type'] ?? null) {
        'notification.sent' => // Notificación enviada
        'notification.failed' => // Notificación fallida
        default => // Otro evento
    };
});
```

## Configuración avanzada

```php
// config/mogotes-laravel.php

return [
    'base_url' => env('MOGOTES_SERVER_URL', 'https://api.mogotes.com'),
    'api_key' => env('MOGOTES_API_KEY'),
    'timeout_seconds' => env('MOGOTES_TIMEOUT_SECONDS', 5),
    
    'feature_flags' => [
        'ttl_seconds' => env('MOGOTES_FEATURE_FLAGS_TTL_SECONDS', 300),
        'cache_enabled' => env('MOGOTES_FEATURE_FLAGS_CACHE_ENABLED', true),
    ],
    
    'webhooks' => [
        'register_route' => env('MOGOTES_WEBHOOK_REGISTER_ROUTE', true),
        'path' => env('MOGOTES_WEBHOOK_PATH', '/mogotes/webhook'),
        'secret' => env('MOGOTES_WEBHOOK_SECRET'),
    ],
];
```

## Excepciones

El paquete lanza excepciones específicas para facilitar el manejo de errores:

- `MogotesUnauthorizedException` - API key inválida o expirada
- `MogotesApiException` - Error genérico de la API
- `MogotesConnectionException` - No se pudo conectar al servidor
- `MogotesRateLimitException` - Límite de requests excedido
- `MogotesIdempotencyConflictException` - Conflicto de idempotencia
- `InvalidWebhookSignatureException` - Firma de webhook inválida

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Ver [License File](LICENSE.md) para más información.
