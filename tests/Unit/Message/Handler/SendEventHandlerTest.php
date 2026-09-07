<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Message\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;

#[CoversClass(SendEventHandler::class)]
final class SendEventHandlerTest extends TestCase
{
    #[Test]
    public function it_sends_the_event(): void
    {
        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->pixels = [new Pixel('1234', 's3cr3t')];

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendEvent')->with($event);

        (new SendEventHandler($client))(new SendEvent($event));
    }

    #[Test]
    public function it_skips_pixels_without_an_access_token(): void
    {
        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->pixels = [new Pixel('no-token'), new Pixel('1234', 's3cr3t')];

        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendEvent')->willReturnCallback(
            static function (Event $event) use (&$sent): void {
                $sent = $event;
            },
        );

        (new SendEventHandler($client))(new SendEvent($event));

        self::assertInstanceOf(Event::class, $sent);
        self::assertEquals([new Pixel('1234', 's3cr3t')], $sent->pixels);
    }

    #[Test]
    public function it_does_not_send_when_no_pixel_has_an_access_token(): void
    {
        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->pixels = [new Pixel('no-token')];

        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendEvent');

        (new SendEventHandler($client))(new SendEvent($event));
    }

    #[Test]
    public function it_does_not_mutate_the_event_it_was_given(): void
    {
        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->pixels = [new Pixel('no-token'), new Pixel('1234', 's3cr3t')];

        $sent = null;
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendEvent')->willReturnCallback(
            static function (Event $event) use (&$sent): void {
                $sent = $event;
            },
        );

        (new SendEventHandler($client))(new SendEvent($event));

        // The application may still hold the event when the command is handled synchronously
        self::assertNotSame($event, $sent);
        self::assertSame(
            ['no-token', '1234'],
            array_map(static fn (Pixel $pixel): string => $pixel->id, $event->pixels),
        );
    }
}
