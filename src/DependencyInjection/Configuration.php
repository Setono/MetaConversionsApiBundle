<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Setono\Consent\DefaultConsents;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_meta_conversions_api');

        $children = $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children();

        $children
            ->arrayNode('consent')
                ->info('If enabled, the bundle will only track events if a consent is granted')
                ->canBeEnabled()
                ->children()
                    ->scalarNode('category')->defaultValue(DefaultConsents::CONSENT_MARKETING)->end()
                ->end()
            ->end()
        ;

        // Client side tracking is enabled by default when the tag bag bundle is installed
        $clientSide = $children
            ->arrayNode('client_side')
            ->info('Configuration for client side tracking');

        if (self::isTagBagBundleInstalled()) {
            $clientSide->canBeDisabled();
        } else {
            $clientSide->canBeEnabled();
        }

        $children
            ->arrayNode('server_side')
                ->info('Configuration for server side tracking')
                ->canBeDisabled()
                ->children()
                    ->scalarNode('message_bus')
                        ->info('The Messenger bus the SendEvent command is dispatched on. Defaults to the application\'s default bus')
                        ->defaultValue('messenger.default_bus')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
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
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('user_agent')
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private static function isTagBagBundleInstalled(): bool
    {
        return InstalledVersions::isInstalled('setono/tag-bag-bundle') &&
            InstalledVersions::satisfies(new VersionParser(), 'setono/tag-bag-bundle', '^3.0');
    }
}
