<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterEmptyUserAgentSubscriber;

#[CoversClass(FilterEmptyUserAgentSubscriber::class)]
final class FilterEmptyUserAgentSubscriberTest extends TestCase
{
    #[Test]
    public function it_stops_a_website_event_without_a_user_agent(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new FilterEmptyUserAgentSubscriber())->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_stops_a_website_event_with_an_empty_user_agent(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = '';

        $event = new ConversionsApiEventRaised($metaEvent);

        (new FilterEmptyUserAgentSubscriber())->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_a_website_event_with_a_user_agent(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->userData->clientUserAgent = 'Chrome';

        $event = new ConversionsApiEventRaised($metaEvent);

        (new FilterEmptyUserAgentSubscriber())->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * Events raised from a console command, a message handler or a webhook have no user agent by definition
     */
    #[Test]
    #[DataProvider('nonWebsiteActionSources')]
    public function it_does_not_stop_a_non_website_event(string $actionSource): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_PURCHASE, $actionSource));

        (new FilterEmptyUserAgentSubscriber())->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonWebsiteActionSources(): iterable
    {
        yield 'system generated' => [Event::ACTION_SOURCE_SYSTEM_GENERATED];
        yield 'physical store' => [Event::ACTION_SOURCE_PHYSICAL_STORE];
        yield 'email' => [Event::ACTION_SOURCE_EMAIL];
        yield 'phone call' => [Event::ACTION_SOURCE_PHONE_CALL];
        yield 'chat' => [Event::ACTION_SOURCE_CHAT];
        yield 'other' => [Event::ACTION_SOURCE_OTHER];
    }
}
