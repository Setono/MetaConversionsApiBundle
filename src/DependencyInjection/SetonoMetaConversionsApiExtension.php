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
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        /** @psalm-suppress ReservedWord */
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
