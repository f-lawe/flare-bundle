<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flawe\FlareBundle\EventSubscriber\ConsoleEventSubscriber;
use Flawe\FlareBundle\EventSubscriber\KernelEventSubscriber;
use Flawe\FlareBundle\FlareFactory;
use Flawe\FlareBundle\Support\RuntimeContainer;
use Spatie\FlareClient\Flare;
use Symfony\Component\Console\ConsoleEvents;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Arguments and Flare services are declared by FlareBundle::loadExtension(), from the configuration.
    $services->set(RuntimeContainer::class)
        ->factory([FlareFactory::class, 'create'])
    ;

    $services->alias('flare', Flare::class)->public();

    $services->set(KernelEventSubscriber::class)
        ->args([
            '$flare' => service('flare'),
        ])
        ->tag('kernel.event_subscriber')
    ;

    if (class_exists(ConsoleEvents::class)) {
        $services->set(ConsoleEventSubscriber::class)
            ->args([
                '$flare' => service('flare'),
            ])
            ->tag('kernel.event_subscriber')
        ;
    }
};
