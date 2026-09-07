<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterConfiguredUserAgentsSubscriber;

#[CoversClass(FilterConfiguredUserAgentsSubscriber::class)]
final class FilterConfiguredUserAgentsSubscriberTest extends TestCase
{
    #[Test]
    public function it_stops_when_user_agent_matches(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'i_am_a_bot';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_when_user_agent_does_not_match(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'Chrome';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function it_logs_why_the_event_was_dropped(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'i_am_a_bot';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('user agent'));

        (new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot'], $logger))->filter(new ConversionsApiEventRaised($metaEvent));
    }

    #[Test]
    public function it_matches_case_insensitively(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'I_AM_A_BOT/1.0';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_without_a_user_agent(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_when_no_user_agents_are_configured(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'i_am_a_bot';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber([]);
        $subscriber->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function it_throws_when_the_fragments_do_not_compile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // An unescaped delimiter turns the rest of the fragment into modifiers. Previously preg_match() returned
        // false here and the filter silently stopped matching anything
        new FilterConfiguredUserAgentsSubscriber(['foo#bar']);
    }

    #[Test]
    public function it_throws_for_an_invalid_fragment(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FilterConfiguredUserAgentsSubscriber(['(unbalanced']);
    }
}
