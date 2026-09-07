<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Integration\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Setono\Consent\DefaultConsents;
use Setono\MetaConversionsApiBundle\Context\Fbc\CachedFbcContext;
use Setono\MetaConversionsApiBundle\Context\Fbp\CachedFbpContext;
use Setono\MetaConversionsApiBundle\DependencyInjection\SetonoMetaConversionsApiExtension;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddLibraryToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreTestEventCodeSubscriber;
use Setono\TagBagBundle\SetonoTagBagBundle;

#[CoversClass(SetonoMetaConversionsApiExtension::class)]
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

    #[Test]
    public function it_sets_parameters(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.consent.enabled', false);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.consent.category', DefaultConsents::CONSENT_MARKETING);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.client_side.enabled', true);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.server_side.enabled', true);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.pixels', []);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.filters.user_agent', []);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.test_event_code.value', null);
        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.test_event_code.query_parameter', false);
    }

    #[Test]
    public function it_loads_client_side_event_subscribers_when_client_side_is_enabled(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(AddEventToTagBagSubscriber::class);
        $this->assertContainerBuilderHasService(AddLibraryToTagBagSubscriber::class);
    }

    #[Test]
    public function it_does_not_load_client_side_event_subscribers_when_client_side_is_disabled(): void
    {
        $this->load([
            'client_side' => false,
        ]);

        $this->assertContainerBuilderNotHasService(AddEventToTagBagSubscriber::class);
        $this->assertContainerBuilderNotHasService(AddLibraryToTagBagSubscriber::class);
    }

    #[Test]
    public function it_tags_the_cached_contexts_as_resettable(): void
    {
        $this->load();

        // Without this tag the cached values survive between requests in worker mode runtimes
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CachedFbcContext::class, 'kernel.reset', ['method' => 'reset']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CachedFbpContext::class, 'kernel.reset', ['method' => 'reset']);
    }

    #[Test]
    public function it_does_not_register_the_test_event_code_subscriber_by_default(): void
    {
        // kernel.debug is not set in this test, so the query parameter must not be honoured
        $this->load();

        $this->assertContainerBuilderNotHasService(StoreTestEventCodeSubscriber::class);
    }

    #[Test]
    public function it_registers_the_test_event_code_subscriber_when_the_query_parameter_is_enabled(): void
    {
        $this->load([
            'test_event_code' => [
                'query_parameter' => true,
            ],
        ]);

        $this->assertContainerBuilderHasService(StoreTestEventCodeSubscriber::class);
    }

    #[Test]
    public function it_treats_an_empty_static_test_event_code_as_none(): void
    {
        // An unset env var resolves to an empty string, which must not be sent to Meta as a test event code
        $this->load([
            'test_event_code' => [
                'value' => '',
            ],
        ]);

        $this->assertContainerBuilderHasParameter('setono_meta_conversions_api.test_event_code.value', null);
    }

    #[Test]
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
