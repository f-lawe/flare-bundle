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

### Customising Flare services
Flare's internal services (`Tracer`, `Sampler`, `Sender`, recorders, etc.) are private services of the Symfony container, listed by `debug:container`. Inject `Flare` and use its properties (`$flare->tracer`, `$flare->log()`, etc.) rather than these services. To change one of them, decorate it the Symfony way:

```php
namespace App\Flare;

use Spatie\FlareClient\EntryPoint\EntryPoint;
use Spatie\FlareClient\Sampling\Sampler;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(Sampler::class)]
final class CustomSampler implements Sampler
{
    public function __construct(
        #[AutowireDecorated] private readonly Sampler $flareSampler,
    ) {
    }

    public function shouldSample(EntryPoint $entryPoint, ?bool $parentSampled = null): bool
    {
        return $this->flareSampler->shouldSample($entryPoint, $parentSampled);
    }
}
```

Every Flare service using the decorated one gets the decorator.

## Configuration

### Sender
Payloads are sent to Flare with cURL by default. Use `sender` to pick another transport (`curl`, `daemon`, `guzzle` or `null`), with its options:

```yaml
flare:
    sender:
        type: daemon
        options:
            daemon_url: '%env(default::FLARE_DAEMON_URL)%'
```

See [Flare daemon](https://flareapp.io/docs/php/general/flare-daemon) to run the daemon.

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
Enable log collection to send your application logs to Flare. Logs are also required for `collect.logs_with_errors`.

```yaml
flare:
    log:
        enable: true
        level: warning
```

The bundle hooks into the Symfony logger automatically, while keeping your existing log output (records are only sent to Flare when `log.enable` is `true`):
- with [MonologBundle](https://symfony.com/doc/current/logging.html#monolog), a `flare` Monolog handler is registered and receives every channel. Restrict channels in `config/packages/monolog.yaml`:

  ```yaml
  monolog:
      handlers:
          flare:
              channels: ['!event', '!doctrine']
  ```
- without Monolog, the default `logger` service is decorated: messages are still written by the Symfony logger, and also sent to Flare.

Set `log.auto_register: false` to disable this integration and only record logs manually with `$flare->log()->record()`.

Logs are sent at the end of each request or console command. See [Logs](https://flareapp.io/docs/php/logs/introduction).

### Messenger
With [Symfony Messenger](https://symfony.com/doc/current/messenger.html), the bundle traces messages sent to transports and handled by `messenger:consume` workers:
- sending a message records a `Queueing` span, and stamps the message with the current trace so that the worker continues it;
- each message handled by a worker is traced as a job (with the `job` sampling entry point), and its errors are reported to Flare, on every attempt.

Messages handled synchronously (without transport) are not traced as jobs.

Use `collect.jobs` to disable this tracing (errors are still reported), or to ignore some messages:

```yaml
flare:
    collect:
        jobs:
            ignored_messages: ['App\Message\Ping', 'App\Message\Health*']
```

### Long-running processes
In long-running processes, every unit of work is traced as a [subtask](https://flareapp.io/docs/php/general/integrating-into-a-framework#queue-workers-and-long-running-processes): each request with a FrankenPHP worker or RoadRunner, each message with `messenger:consume`. Use `subtasks` (`auto`, `always` or `never`) to override this detection:

```yaml
flare:
    subtasks: never
```

