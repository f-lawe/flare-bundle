<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flawe\FlareBundle\EventSubscriber\KernelEventSubscriber;
use Flawe\FlareBundle\FlareFactory;
use Spatie\FlareClient\Flare;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Flare::class)
        ->factory([FlareFactory::class, 'init'])
        ->args([
            '$parameterBag' => service('parameter_bag'),
        ])
        ->alias('flare', Flare::class)
    ;

    $services->set(KernelEventSubscriber::class)
        ->args([
            '$flare' => service('flare'),
        ])
        ->tag('kernel.event_subscriber')
    ;
};
