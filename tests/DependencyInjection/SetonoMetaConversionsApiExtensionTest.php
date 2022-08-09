<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\MetaConversionsApiBundle\DependencyInjection\SetonoMetaConversionsApiExtension;
use Setono\TagBagBundle\SetonoTagBagBundle;

/**
 * @covers \Setono\MetaConversionsApiBundle\DependencyInjection\SetonoMetaConversionsApiExtension
 */
final class SetonoMetaConversionsApiExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoMetaConversionsApiExtension(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', ['SetonoTagBagBundle' => SetonoTagBagBundle::class]);
    }

    /**
     * @test
     */
    public function it_sets_parameters(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.consent.enabled', false);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.client_side.enabled', true);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.server_side.enabled', true);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.pixels', []);
    }
}
