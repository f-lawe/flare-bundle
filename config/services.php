<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flawe\FlareBundle\EventSubscriber\ConfigurationEventSubscriber;

return static function (ContainerConfigurator $container): void {
    $container->services()->set(ConfigurationEventSubscriber::class)
        ->args([
            '$logger' => service('logger')->ignoreOnInvalid(),
        ])
        ->tag('kernel.event_subscriber');
};
