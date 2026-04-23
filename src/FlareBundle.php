<?php

declare(strict_types=1);

namespace Flawe\FlareBundle;

use Spatie\FlareClient\Flare;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FlareBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
                ->stringNode('key')->isRequired()->cannotBeEmpty()->end()
                ->booleanNode('trace')->defaultFalse()->end()
                ->arrayNode('censor')
                ->children()
                    ->booleanNode('client_ips')->defaultFalse()->end()
                    ->arrayNode('body_fields')->scalarPrototype()->end()->end()
                    ->arrayNode('headers')->scalarPrototype()->end()->end()
                    ->booleanNode('cookies')->defaultFalse()->end()
                    ->booleanNode('session')->defaultFalse()->end()
                ->end()
            ->end()
        ->end()
        ;
    }

    /**
     * @param array{
     *   key: string
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('flare.key', $config['key']);

        FlareFactory::createFlare($config);

        $container->services()->set('flare', Flare::class)->factory([FlareFactory::class, 'loadFlare']);
        $container->services()->alias(Flare::class, 'flare');
    }
}
