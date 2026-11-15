# Symfony Flare Bundle
[![Versions](https://img.shields.io/packagist/v/flawe/flare-bundle
)](https://packagist.org/packages/flawe/flare-bundle)
[![Downloads](https://img.shields.io/packagist/dt/flawe/flare-bundle
)](https://packagist.org/packages/flawe/flare-bundle/stats)
[![Licence](https://img.shields.io/packagist/l/flawe/flare-bundle
)](./LICENCE.md)
[![GitHub Actions](https://img.shields.io/github/actions/workflow/status/f-lawe/flare-bundle/pr-checks.yaml)](https://github.com/f-lawe/flare-bundle/actions/workflows/pr-checks.yaml)

Send Symfony errors to Flare! This project is the Symfony counterpart of the [Laravel Flare](https://github.com/spatie/laravel-flare) package.

## Installation
Install the package via composer:

```bash
composer require flawe/flare-bundle
```

Add the bundle to your Symfony application by adding it to the `config/bundles.php` file:

```php
return [
    // ...
    Flawe\FlareBundle\FlareBundle::class => ['all' => true],
];
```

Add your Flare API key to your `.env` file:

```ini
FLARE_KEY=your-flare-key
```

And finally, add the file `config/packages/flare.yaml` and fill it with your settings:

```yaml
flare:
    key: '%env(default::FLARE_KEY)%'
    trace: false
    censor:
        client_ips: true
        body_fields: []
        headers: []
        cookies: true
        session: true
```

When `key` is empty or missing, Flare is disabled: nothing is sent and no error is raised.

## Usage
The bundle will automatically report all errors to Flare. You can also inject Flare when you need it:

```php
namespace App\Controller;

use Spatie\FlareClient\Flare;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SomeController
{
    #[Route('/some-action')]
    public function someAction(Flare $flare): Response
    {
        $flare->tracer->span('Do something', function () {
            // Do something
        });

        return new Response('Some Response');
    }
}
```

## Configuration

### Sender
Payloads are sent to Flare with cURL by default. Use `sender` to pick another transport (`curl`, `daemon`, `guzzle`, `null` or a class implementing `Spatie\FlareClient\Senders\Sender`), with its options:

```yaml
flare:
    sender:
        type: daemon
        options:
            daemon_url: '%env(default::FLARE_DAEMON_URL)%'
```

See the [Flare daemon documentation](https://flareapp.io/docs/php/general/flare-daemon) to run the daemon.

### Sampling
Traces are sampled at 10% by default. Use `sampling` to change the strategy (`rate`, `always` or `never`), the rate, and add per-entry-point rules:

```yaml
flare:
    sampling:
        rate: 0.2
        rules:
            - { type: path, pattern: '/health', rate: 0 }
            - { type: route, pattern: 'app_checkout_*', rate: 1 }
            - { type: command, pattern: 'app:import*', rate: 0.5 }
```

Rule types are `url`, `path`, `route`, `command` and `job`; patterns accept `*` as a wildcard. `route` rules match the Symfony route **name**. See [Sampling](https://flareapp.io/docs/php/performance/sampling).

### Logs
With MonologBundle installed and `log: true`, the bundle registers a `flare` Monolog handler sending your application logs to Flare. Logs are also required for `collect.logs_with_errors`.

```yaml
flare:
    log: true
    minimal_log_level: warning
```

Restrict channels by configuring the handler in `config/packages/monolog.yaml` (e.g. `monolog.handlers.flare.channels: ['!event']`), or disable it with `monolog_handler: false`.

