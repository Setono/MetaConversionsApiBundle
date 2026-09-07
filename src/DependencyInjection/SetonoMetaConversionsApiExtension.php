<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoMetaConversionsApiExtension extends Extension
{
    /**
     * Client side tracking renders its tags through the tag bag, so it cannot work without that bundle
     *
     * @throws \LogicException if the tag bag bundle is not installed and registered
     */
    private static function assertTagBagBundleIsAvailable(ContainerBuilder $container): void
    {
        $requirement = sprintf(
            'You need to install %s %s and register SetonoTagBagBundle to use client side tracking, or set setono_meta_conversions_api.client_side.enabled to false',
            InstalledBundles::TAG_BAG_BUNDLE,
            InstalledBundles::TAG_BAG_BUNDLE_CONSTRAINT,
        );

        if (!InstalledBundles::hasTagBagBundle()) {
            throw new \LogicException($requirement);
        }

        if (!$container->hasParameter('kernel.bundles')) {
            throw new \LogicException('The kernel.bundles parameter has not been set. Are you not using this in a Symfony application context?');
        }

        $bundles = $container->getParameter('kernel.bundles');
        if (!is_array($bundles) || !array_key_exists('SetonoTagBagBundle', $bundles)) {
            throw new \LogicException('The SetonoTagBagBundle is not in the list of enabled bundles. ' . $requirement);
        }
    }

    /**
     * @param array<array-key, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        // The test event code query parameter is a debugging feature, hence it follows kernel.debug by default
        return new Configuration($container->hasParameter('kernel.debug') && true === $container->getParameter('kernel.debug'));
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @var array{consent: array{enabled: bool, category: string}, client_side: array{enabled: bool}, server_side: array{enabled: bool, message_bus: string}, pixels: array<array-key, array{id: string, access_token: string}>, http_client: string, test_event_code: array{query_parameter: bool, value: string|null}, cookies: array{fbp: bool, fbc: bool, domain: string|null, lifetime: string}, filters: array{user_agent: list<string>}} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        // The XML format is deprecated since Symfony 7.4 and removed in 8.0. Migrate to PHP config before adding Symfony 8 support
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_meta_conversions_api.consent.enabled', $config['consent']['enabled']);
        $container->setParameter('setono_meta_conversions_api.consent.category', $config['consent']['category']);
        $container->setParameter('setono_meta_conversions_api.pixels', $config['pixels']);
        $container->setParameter('setono_meta_conversions_api.filters.user_agent', $config['filters']['user_agent']);

        $cookieDomain = $config['cookies']['domain'];
        $container->setParameter('setono_meta_conversions_api.cookies.domain', '' === $cookieDomain ? null : $cookieDomain);
        $container->setParameter('setono_meta_conversions_api.cookies.lifetime', $config['cookies']['lifetime']);

        $testEventCode = $config['test_event_code']['value'];
        $container->setParameter('setono_meta_conversions_api.test_event_code.value', '' === $testEventCode ? null : $testEventCode);
        $container->setParameter('setono_meta_conversions_api.test_event_code.query_parameter', $config['test_event_code']['query_parameter']);

        // The reference to this alias is optional, so an application without symfony/http-client simply lets the
        // SDK fall back to php-http/discovery
        $container->setAlias('setono_meta_conversions_api.http_client', $config['http_client']);

        $loader->load('services.xml');

        foreach (['fbp', 'fbc'] as $cookie) {
            if (!$config['cookies'][$cookie]) {
                $container->removeDefinition(sprintf('Setono\\MetaConversionsApiBundle\\EventSubscriber\\Store%sSubscriber', ucfirst($cookie)));
            }
        }

        if ($config['test_event_code']['query_parameter']) {
            $loader->load('services/conditional/test_event_code.xml');
        }

        if ($config['client_side']['enabled']) {
            self::assertTagBagBundleIsAvailable($container);

            $loader->load('services/conditional/client_side.xml');
        }

        if ($config['server_side']['enabled']) {
            // The bundle deliberately does _not_ register a Messenger bus of its own: adding an entry to
            // framework.messenger.buses removes FrameworkBundle's default 'messenger.bus.default' and can even
            // make the container fail to boot in applications that define a bus without a default_bus.
            // Instead we alias the bus the application tells us to use.
            $container->setAlias('setono_meta_conversions_api.message_bus', $config['server_side']['message_bus']);

            $loader->load('services/conditional/server_side.xml');
        }
    }
}
