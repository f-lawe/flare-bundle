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
    key: '%env(FLARE_KEY)%'
    trace: false
    censor:
        client_ips: true
        body_fields: []
        headers: []
        cookies: true
        session: true
```

The minimum configuration requires the `key` option.

## Usage
The bundle will automatically report all errors to Flare.

On top of that, you can inject Flare to monitor perfomance:

```php
namespace App\Controller;

use Spatie\FlareClient\Flare;
use Symfony\Component\HttpFoundation\Response;

class SomeController
{
    #[Route('/some-action')]
    public function someAction(Flare $flare): Response
    {
        $flare->application()->recordStart();
        // Do something
        $flare->application()->recordEnd();

        return new Response('Some Response');
    }
}
```
