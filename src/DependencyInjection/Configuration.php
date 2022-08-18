<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_meta_conversions_api');

        $rootNode = $treeBuilder->getRootNode();

        $clientSideDefault = 'canBeEnabled';
        if (InstalledVersions::isInstalled('setono/tag-bag-bundle')
            && InstalledVersions::satisfies(new VersionParser(), 'setono/tag-bag-bundle', '^3.0')) {
            $clientSideDefault = 'canBeDisabled';
        }

        /** @psalm-suppress MixedMethodCall, PossiblyUndefinedMethod, PossiblyNullReference */
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('consent')
                    ->info('If enabled, the bundle will only track events if a consent is granted')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('client_side')
                    ->info('Configuration for client side tracking')
                    ->{$clientSideDefault}()
                ->end()
                ->arrayNode('server_side')
                    ->info('Configuration for server side tracking')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('pixels')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('access_token')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('filters')
                    ->children()
                        ->arrayNode('user_agent')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
