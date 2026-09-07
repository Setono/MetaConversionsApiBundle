<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Double;

use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;

/**
 * Records every event handed to the SDK client, so an end to end test can inspect what would have been sent
 *
 * Built through a factory because a container definition cannot hold a live object
 */
final class RecordingConversionsApiClientFactory
{
    /** @var list<Event> */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }

    public static function create(): ClientInterface
    {
        return new class() implements ClientInterface {
            public function sendEvent(Event $event): void
            {
                RecordingConversionsApiClientFactory::$events[] = $event;
            }
        };
    }
}
