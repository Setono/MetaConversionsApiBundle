<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateRequestPropertiesSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(PopulateRequestPropertiesSubscriber::class)]
final class PopulateRequestPropertiesSubscriberTest extends TestCase
{
    #[Test]
    public function it_does_nothing_without_a_request(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new PopulateRequestPropertiesSubscriber(new RequestStack()))->populate($event);

        self::assertNull($event->event->eventSourceUrl);
        self::assertNull($event->event->userData->clientIpAddress);
        self::assertNull($event->event->userData->clientUserAgent);
    }

    #[Test]
    public function it_populates_the_request_properties(): void
    {
        $request = Request::create('https://example.com/jeans?colour=blue', server: ['REMOTE_ADDR' => '203.0.113.4']);
        $request->headers->set('User-Agent', 'Chrome');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new PopulateRequestPropertiesSubscriber($requestStack))->populate($event);

        // The full url including the query string is sent, which the README calls out
        self::assertSame('https://example.com/jeans?colour=blue', $event->event->eventSourceUrl);
        self::assertSame('203.0.113.4', $event->event->userData->clientIpAddress);
        self::assertSame('Chrome', $event->event->userData->clientUserAgent);
    }
}
