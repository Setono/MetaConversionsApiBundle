<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_meta_conversions_api');

        $rootNode = $treeBuilder->getRootNode();

        /** @psalm-suppress MixedMethodCall, PossiblyUndefinedMethod, PossiblyNullReference */
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('client_side')
                    ->info('Configuration for client side tracking')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('server_side')
                    ->info('Configuration for server side tracking')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('pixels')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('access_token')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
