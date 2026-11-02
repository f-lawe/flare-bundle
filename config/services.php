<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flawe\FlareBundle\EventSubscriber\ConsoleEventSubscriber;
use Flawe\FlareBundle\EventSubscriber\KernelEventSubscriber;
use Flawe\FlareBundle\FlareFactory;
use Flawe\FlareBundle\Support\Container;
use Spatie\FlareClient\EntryPoint\EntryPointResolver;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\Logger;
use Spatie\FlareClient\Reporter;
use Spatie\FlareClient\Resources\Resource;
use Spatie\FlareClient\Scopes\Scope;
use Spatie\FlareClient\Support\BackTracer;
use Spatie\FlareClient\Support\Ids;
use Spatie\FlareClient\Support\Lifecycle;
use Spatie\FlareClient\Support\SentReports;
use Spatie\FlareClient\Time\Time;
use Spatie\FlareClient\Tracer;
use Symfony\Component\Console\ConsoleEvents;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(Container::class)
        ->factory([FlareFactory::class, 'create'])
        ->args([
            '$config'    => param('flare.config'),
            '$overrides' => tagged_locator('flare.service'),
        ])
    ;

    // Must never be tagged "flare.service": the locator would resolve them back to themselves.
    $flareServiceClasses = [
        Flare::class,
        Lifecycle::class,
        Tracer::class,
        Logger::class,
        Reporter::class,
        BackTracer::class,
        SentReports::class,
        Ids::class,
        Time::class,
        EntryPointResolver::class,
        Resource::class,
        Scope::class,
    ];

    foreach ($flareServiceClasses as $class) {
        $services->set($class)
            ->factory([service(Container::class), 'get'])
            ->args([$class])
        ;
    }

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
