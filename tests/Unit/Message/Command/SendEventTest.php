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
    public function it_carries_the_prepared_payload_and_the_pixel_ids(): void
    {
        $message = SendEvent::fromEvent(self::event());

        self::assertSame('Purchase', $message->eventName);
        self::assertSame(['1234'], $message->pixelIds);
        self::assertSame('TEST1234', $message->testEventCode);
        self::assertArrayHasKey('user_data', $message->payload);
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

    #[Test]
    public function it_carries_the_hashed_personal_data(): void
    {
        $message = SendEvent::fromEvent(self::event());

        $userData = $message->payload['user_data'];
        self::assertIsArray($userData);
        self::assertSame([hash('sha256', 'customer@example.com')], $userData['em']);
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
