<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

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
