<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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

    #[Test]
    public function it_logs_why_the_event_was_dropped(): void
    {
        // 'why did my events stop showing up' is the number one support question, so a dropped event must say so
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('bot'));

        (new FilterBotsSubscriber(self::botDetector(true), $logger))->filter(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }

    #[Test]
    public function it_does_not_log_when_the_event_passes(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method(self::anything());

        (new FilterBotsSubscriber(self::botDetector(false), $logger))->filter(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }

    /**
     * An event raised from a console command or a message handler is not a request, so the bot check does not
     * apply to it
     */
    #[Test]
    public function it_does_not_stop_a_non_website_event(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_PURCHASE, Event::ACTION_SOURCE_SYSTEM_GENERATED));

        (new FilterBotsSubscriber(self::botDetector(true)))->filter($event);

        self::assertFalse($event->isPropagationStopped());
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
