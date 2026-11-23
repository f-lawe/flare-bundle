<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flawe\FlareBundle\EventSubscriber\ConsoleEventSubscriber;
use Flawe\FlareBundle\EventSubscriber\KernelEventSubscriber;
use Flawe\FlareBundle\EventSubscriber\MessengerEventSubscriber;
use Spatie\FlareClient\Flare;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Flare services and their RuntimeContainer are declared by FlareBundle::loadExtension(), from the configuration.
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

    if (class_exists(WorkerMessageReceivedEvent::class)) {
        $services->set(MessengerEventSubscriber::class)
            ->args([
                '$flare' => service('flare'),
            ])
            ->tag('kernel.event_subscriber')
        ;
    }
};
