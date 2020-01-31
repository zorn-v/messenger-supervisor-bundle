<?php

namespace ZornV\Symfony\MessengerSupervisorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('messenger_supervisor');

        $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->normalizeKeys(false)
                ->children()
                    ->arrayNode('receivers')
                        ->scalarPrototype()->end()
                    ->end()
                    ->scalarNode('memory-limit')->end()
                    ->integerNode('time-limit')->end()
                    ->integerNode('limit')->end()
                    ->integerNode('sleep')->end()
                    ->scalarNode('bus')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
