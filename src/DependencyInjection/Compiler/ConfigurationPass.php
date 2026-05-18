<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection\Compiler;

use Flawe\FlareBundle\EventSubscriber\ConfigurationEventSubscriber;
use Psr\Log\LoggerInterface;
use Spatie\FlareClient\FlareConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        echo "ConfigurationPass";
        // echo ConfigurationEventSubscriber::class;
        // echo "<pre>";
        // // print_r($container->getAliases());
        // echo "</pre>";
        // $logger = $container->getAlias('logger');
        // echo $logger::class;
        // $key = $container->getParameter('flare.key');
        // /** @var string $key */
        // $key = $container->resolveEnvPlaceholders($key, true);

        // FlareConfig::make($key)->useDefaults();

        // @todo

        // if ($container->has('logger')) {
        //     echo "LOGGER";
        //     $logger = $container->get('logger');
        //     // $logger->warning('The "flare_bundle.some_setting" is not properly set. Please configure it.');
        // }
    }
}
