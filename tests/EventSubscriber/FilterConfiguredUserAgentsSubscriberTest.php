<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterConfiguredUserAgentsSubscriber;

/**
 * @covers \Setono\MetaConversionsApiBundle\EventSubscriber\FilterConfiguredUserAgentsSubscriber
 */
final class FilterConfiguredUserAgentsSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_stops_when_user_agent_matches(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'i_am_a_bot';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    /**
     * @test
     */
    public function it_does_not_stop_when_user_agent_does_not_match(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'Chrome';

        $event = new ConversionsApiEventRaised($metaEvent);
        $subscriber = new FilterConfiguredUserAgentsSubscriber(['i_am_a_bot']);
        $subscriber->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }
}
