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

    /**
     * @test
     */
    public function it_does_not_load_client_side_event_subscribers_when_client_side_is_disabled(): void
    {
        $this->load([
            'client_side' => false,
        ]);

        $this->assertContainerBuilderNotHasService('setono_meta_conversions_api.event_subscriber.add_event_to_tag_bag');
        $this->assertContainerBuilderNotHasService('setono_meta_conversions_api.event_subscriber.add_library_to_tag_bag');
    }

    /**
     * @test
     */
    public function it_sets_user_agent_filter(): void
    {
        $this->load([
            'filters' => [
                'user_agent' => [
                    'a_robot',
                    'also_a_robot',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.filters.user_agent', ['a_robot', 'also_a_robot']);
    }
}
