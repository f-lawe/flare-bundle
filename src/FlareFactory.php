<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareProvider;
use Spatie\FlareClient\Support\Container;

class FlareFactory
{
    private static ?Flare $flare = null;

    /**
     * @param array<string, mixed> $config
     */
    public static function init(array $config): Flare
    {
        if (self::$flare instanceof Flare) {
            return self::$flare;
        }

        $flareConfig = FlareConfig::fromSymfonyConfig($config);

        $container = Container::instance();

        $provider = new FlareProvider($flareConfig, $container);
        $provider->register();
        $provider->boot();

        self::$flare = $container->get(Flare::class);
        self::$flare->registerFlareHandlers();

        return self::$flare;
    }

    public static function reset(): void
    {
        self::$flare = null;
    }
}
