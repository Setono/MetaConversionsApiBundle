<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Message\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;

#[CoversClass(SendEvent::class)]
final class SendEventTest extends TestCase
{
    #[Test]
    public function it_carries_the_prepared_event(): void
    {
        $preparedEvent = SendEvent::fromEvent(self::event())->preparedEvent;

        self::assertSame('Purchase', $preparedEvent->eventName);
        self::assertSame('TEST1234', $preparedEvent->testEventCode);
        self::assertArrayHasKey('user_data', $preparedEvent->payload);
        self::assertEquals([new Pixel('1234')], $preparedEvent->pixels);
    }

    /**
     * The message is written to the transport's storage, and to the failure transport when it fails, so neither
     * the access token nor any raw personal data may travel in it
     */
    #[Test]
    public function it_does_not_carry_the_access_token_or_raw_personal_data(): void
    {
        $serialized = serialize(SendEvent::fromEvent(self::event()));

        self::assertStringNotContainsString('s3cr3t', $serialized);
        self::assertStringNotContainsString('customer@example.com', $serialized);
        self::assertStringNotContainsString('+4512345678', $serialized);
        self::assertStringNotContainsString('Joachim', $serialized);
    }

    /**
     * Event::prepare() keeps the tokens on the pixels, and a caller may well forget withoutAccessTokens(). The
     * constructor does not leave that to chance
     */
    #[Test]
    public function it_strips_the_access_tokens_whatever_it_is_given(): void
    {
        $prepared = self::event()->prepare();
        self::assertSame('s3cr3t', $prepared->pixels[0]->accessToken);

        $message = new SendEvent($prepared);

        self::assertNull($message->preparedEvent->pixels[0]->accessToken);
        self::assertStringNotContainsString('s3cr3t', serialize($message));
    }

    #[Test]
    public function it_carries_the_hashed_personal_data(): void
    {
        $userData = SendEvent::fromEvent(self::event())->preparedEvent->payload['user_data'];

        self::assertIsArray($userData);
        self::assertSame([hash('sha256', 'customer@example.com')], $userData['em']);
    }

    #[Test]
    public function it_survives_the_transport(): void
    {
        $message = SendEvent::fromEvent(self::event());

        self::assertEquals($message, unserialize(serialize($message)));
    }

    private static function event(): Event
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels = [new Pixel('1234', 's3cr3t')];
        $event->testEventCode = 'TEST1234';
        $event->userData->email[] = 'customer@example.com';
        $event->userData->phoneNumber[] = '+4512345678';
        $event->userData->firstName[] = 'Joachim';

        return $event;
    }
}
