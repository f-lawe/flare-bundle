<?php

declare(strict_types=1);

namespace Flawe\FlareBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('flare');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->stringNode('key')->isRequired()->cannotBeEmpty()->end()
                ->booleanNode('use_defaults')->defaultTrue()->end()
                ->booleanNode('trace')->end()
                ->arrayNode('collect')
                    ->children()
                        ->booleanNode('errors_with_traces')->end()
                        ->arrayNode('dumps')->end()
                        ->arrayNode('context')->end()
                        ->booleanNode('commands')->end()
                        ->booleanNode('requests')->end()
                        ->booleanNode('cache_events')->end()
                        ->booleanNode('logs_with_errors')->end()
                        ->booleanNode('queries')->end()
                        ->booleanNode('transactions')->end()
                        ->booleanNode('external_http')->end()
                        ->booleanNode('filesystem_operations')->end()
                        ->booleanNode('git_info')->end()
                        ->booleanNode('views')->end()
                        ->booleanNode('glows')->end()
                        ->booleanNode('stack_frame_arguments')->end()
                        ->booleanNode('server_info')->end()
                    ->end()
                ->end()
                ->arrayNode('censor')
                    ->children()
                        ->booleanNode('client_ips')->end()
                        ->booleanNode('cookies')->end()
                        ->booleanNode('session')->end()
                        ->arrayNode('headers')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('body_fields')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $treeBuilder;
    }
}
