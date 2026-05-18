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
        ->end();

        return $treeBuilder;
    }
}
