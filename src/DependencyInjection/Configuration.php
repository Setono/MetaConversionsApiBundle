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
    public function __construct(private readonly bool $debug = false)
    {
    }

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
            ->arrayNode('test_event_code')
                ->info('Send events as test events, see https://developers.facebook.com/docs/marketing-api/conversions-api/using-the-api#testEvents')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('query_parameter')
                        ->info('If enabled, the _testEventCode/_test_event_code query parameter sets the test event code for the rest of the visitor\'s session. Defaults to the value of kernel.debug')
                        ->defaultValue($this->debug)
                    ->end()
                    ->scalarNode('value')
                        ->info('A static test event code applied to every event, e.g. on a staging environment')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('filters')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('user_agent')
                        ->info('Regular expression fragments without delimiters. Events with a matching user agent are not tracked. Matching is case insensitive')
                        ->scalarPrototype()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(static fn (mixed $fragment): bool => !is_string($fragment) || false === @preg_match('#' . $fragment . '#i', ''))
                                ->thenInvalid('%s is not a valid regular expression fragment. Remember to escape the "#" delimiter')
                            ->end()
                        ->end()
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
