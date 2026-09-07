<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterBotsSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterConfiguredUserAgentsSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterEmptyUserAgentSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateFbpAndFbcPropertiesSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulatePixelsSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateRequestPropertiesSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateTestEventCodePropertySubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\StopPropagationIfNoPixelsHasBeenAddedSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[CoversClass(ConversionsApiEventRaised::class)]
final class ConversionsApiEventRaisedTest extends TestCase
{
    /**
     * The subscribers the bundle registers, in the order the event dispatcher must call them
     *
     * @var list<class-string<EventSubscriberInterface>>
     */
    private const PIPELINE = [
        PopulateRequestPropertiesSubscriber::class,
        PopulateFbpAndFbcPropertiesSubscriber::class,
        PopulateTestEventCodePropertySubscriber::class,
        PopulatePixelsSubscriber::class,
        FilterEmptyUserAgentSubscriber::class,
        FilterConfiguredUserAgentsSubscriber::class,
        FilterBotsSubscriber::class,
        StopPropagationIfNoPixelsHasBeenAddedSubscriber::class,
        AddEventToTagBagSubscriber::class,
        DispatchOnCommandBusSubscriber::class,
    ];

    #[Test]
    public function it_has_context(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT), ['order' => 1]);

        self::assertTrue($event->hasContext('order'));
        self::assertFalse($event->hasContext('customer'));
    }

    /**
     * The documented pipeline only holds as long as the bundle's own listeners keep their relative order, so this
     * pins it down. It is the contract integrators position their own listeners against
     */
    #[Test]
    public function the_pipeline_runs_in_the_documented_order(): void
    {
        $previous = null;

        foreach (self::PIPELINE as $subscriber) {
            $priority = self::priority($subscriber);

            if (null !== $previous) {
                self::assertLessThanOrEqual($previous, $priority, sprintf('%s runs out of order', $subscriber));
            }

            $previous = $priority;
        }
    }

    #[Test]
    public function everything_is_populated_before_your_listeners_run(): void
    {
        foreach ([
            PopulateRequestPropertiesSubscriber::class,
            PopulateFbpAndFbcPropertiesSubscriber::class,
            PopulateTestEventCodePropertySubscriber::class,
            PopulatePixelsSubscriber::class,
        ] as $subscriber) {
            self::assertGreaterThan(ConversionsApiEventRaised::PRIORITY_ENRICH, self::priority($subscriber));
        }
    }

    #[Test]
    public function filtering_and_sending_happen_after_your_listeners(): void
    {
        foreach ([
            FilterEmptyUserAgentSubscriber::class,
            FilterConfiguredUserAgentsSubscriber::class,
            FilterBotsSubscriber::class,
            StopPropagationIfNoPixelsHasBeenAddedSubscriber::class,
            AddEventToTagBagSubscriber::class,
            DispatchOnCommandBusSubscriber::class,
        ] as $subscriber) {
            self::assertLessThan(ConversionsApiEventRaised::PRIORITY_ENRICH, self::priority($subscriber));
        }
    }

    #[Test]
    public function the_sinks_run_last(): void
    {
        self::assertSame(ConversionsApiEventRaised::PRIORITY_SEND, self::priority(AddEventToTagBagSubscriber::class));
        self::assertSame(ConversionsApiEventRaised::PRIORITY_SEND, self::priority(DispatchOnCommandBusSubscriber::class));
    }

    /**
     * @param class-string<EventSubscriberInterface> $subscriber
     */
    private static function priority(string $subscriber): int
    {
        $listener = $subscriber::getSubscribedEvents()[ConversionsApiEventRaised::class] ?? null;

        self::assertIsArray($listener);
        self::assertArrayHasKey(1, $listener);
        self::assertIsInt($listener[1]);

        return $listener[1];
    }
}
