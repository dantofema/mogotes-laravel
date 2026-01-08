# Mogotes Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dantofema/mogotes-laravel.svg?style=flat-square)](https://packagist.org/packages/dantofema/mogotes-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dantofema/mogotes-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dantofema/mogotes-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dantofema/mogotes-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/dantofema/mogotes-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

El paquete `mogotes-laravel` es el SDK oficial para integrar aplicaciones Laravel con el ecosistema de **Mogotes**. Permite gestionar Feature Flags (vía Pennant), enviar Notificaciones y centralizar Logs de forma nativa.

## Instalación

Puedes instalar el paquete a través de Composer:

```bash
composer require dantofema/mogotes-laravel
```

Publica el archivo de configuración:

```bash
php artisan vendor:publish --tag="mogotes-laravel-config"
```

## Configuración

Configura tus credenciales en el archivo `.env`:

```env
# Opcional si usas la instancia oficial (https://mogotes.ar)
MOGOTES_SERVER_URL=https://mogotes.ar

MOGOTES_API_KEY=tu_api_key_aqui
MOGOTES_WEBHOOK_SECRET=tu_secreto_de_webhooks
```

## Uso

### Feature Flags (Laravel Pennant)

El paquete se registra automáticamente como un driver de Pennant.

```php
use Laravel\Pennant\Feature;

if (Feature::active('nueva-interfaz')) {
    // ...
}
```

### Notificaciones

```php
use Dantofema\MogotesLaravel\Facades\Mogotes;

Mogotes::sendNotification(
    template: 'bienvenida-usuario',
    channel: 'email',
    to: 'usuario@ejemplo.com',
    data: ['nombre' => 'Alejandro']
);
```

### Logs Unificados

Configura el canal en `config/logging.php`:

```php
'mogotes' => [
    'driver' => 'custom',
    'via' => \Dantofema\MogotesLaravel\Logging\MogotesLogger::class,
    'level' => 'debug',
],
```

### Webhooks

El paquete registra automáticamente la ruta `/mogotes/webhook`. Puedes escuchar el evento:

```php
use Dantofema\MogotesLaravel\Events\WebhookReceived;

public function handle(WebhookReceived $event)
{
    // $event->payload
}
```

## Testing

```bash
composer test
```

## Créditos

- [alejandro-leone](https://github.com/dantofema)

## Licencia

The MIT License (MIT). Por favor consulta el [Archivo de Licencia](LICENSE.md) para más información.
