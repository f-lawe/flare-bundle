<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Flawe\FlareBundle\Support\Container;
use Psr\Container\ContainerInterface;
use Spatie\FlareClient\FlareProvider;

class FlareFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config, ContainerInterface $overrides): Container
    {
        $container = new Container($overrides);

        $provider = new FlareProvider(FlareConfig::fromSymfonyConfig($config), $container);
        $provider->register();
        $provider->boot();

        return $container;
    }
}
