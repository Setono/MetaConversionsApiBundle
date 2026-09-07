<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterBotsSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(FilterBotsSubscriber::class)]
final class FilterBotsSubscriberTest extends TestCase
{
    #[Test]
    public function it_stops_a_bot_request(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new FilterBotsSubscriber(self::botDetector(true)))->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_a_regular_request(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new FilterBotsSubscriber(self::botDetector(false)))->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * The point of filtering above PRIORITY_ENRICH: an application listener that loads the customer, the order and
     * its addresses must not do any of that for traffic the bundle discards anyway
     */
    #[Test]
    public function it_stops_before_application_listeners_enrich_the_event(): void
    {
        $enriched = false;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new FilterBotsSubscriber(self::botDetector(true)));
        $dispatcher->addListener(
            ConversionsApiEventRaised::class,
            static function () use (&$enriched): void {
                $enriched = true;
            },
            ConversionsApiEventRaised::PRIORITY_ENRICH,
        );

        $dispatcher->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)), ConversionsApiEventRaised::class);

        self::assertFalse($enriched);
    }

    private static function botDetector(bool $isBot): BotDetectorInterface
    {
        return new class($isBot) implements BotDetectorInterface {
            public function __construct(private readonly bool $isBot)
            {
            }

            public function isBot(string $userAgent): bool
            {
                return $this->isBot;
            }

            public function isBotRequest(?Request $request = null): bool
            {
                return $this->isBot;
            }
        };
    }
}
