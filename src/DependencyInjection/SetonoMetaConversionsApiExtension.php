<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;

final class SetonoMetaConversionsApiExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /**
         * @psalm-suppress PossiblyNullArgument
         *
         * @var array{consent: array{enabled: bool}, client_side: array{enabled: bool}, server_side: array{enabled: bool}, pixels: array<array-key, array{id: string, access_token: string}>} $config
         */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter('setono_meta_conversions_api.consent.enabled', $config['consent']['enabled']);
        $container->setParameter('setono_meta_conversions_api.client_side.enabled', $config['client_side']['enabled']);
        $container->setParameter('setono_meta_conversions_api.server_side.enabled', $config['server_side']['enabled']);
        $container->setParameter('setono_meta_conversions_api.pixels', $config['pixels']);

        $loader->load('services.xml');

        if ($config['client_side']['enabled']) {
            $exceptionMessage = 'You need to install the setono/tag-bag-bundle ^3.0 to use the client side tracking';

            Assert::true($container->hasParameter('kernel.bundles'), 'The kernel.bundles parameter has not been set. Are you not using this in a Symfony application context?');

            $bundles = $container->getParameter('kernel.bundles');
            Assert::isArray($bundles);
            Assert::keyExists($bundles, 'SetonoTagBagBundle', 'The SetonoTagBagBundle is not in the list of enabled bundles. ' . $exceptionMessage);

            Assert::true(InstalledVersions::isInstalled('setono/tag-bag-bundle'), $exceptionMessage);
            Assert::true(InstalledVersions::satisfies(new VersionParser(), 'setono/tag-bag-bundle', '^3.0@alpha'), $exceptionMessage);

            $loader->load('services/conditional/event_subscriber.xml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'buses' => [
                    'setono_meta_conversions_api.command_bus' => null,
                ],
            ],
        ]);
    }
}
