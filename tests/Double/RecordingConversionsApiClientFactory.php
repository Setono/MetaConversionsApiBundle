<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Double;

use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;

/**
 * Records every prepared event handed to the SDK client, so an end to end test can inspect what would have been sent
 *
 * Built through a factory because a container definition cannot hold a live object
 */
final class RecordingConversionsApiClientFactory
{
    /** @var list<PreparedEvent> */
    public static array $preparedEvents = [];

    public static function reset(): void
    {
        self::$preparedEvents = [];
    }

    public static function create(): ClientInterface
    {
        return new class() implements ClientInterface {
            public function sendEvent(Event $event): void
            {
                $this->sendPreparedEvent($event->prepare());
            }

            public function sendPreparedEvent(PreparedEvent $preparedEvent): void
            {
                RecordingConversionsApiClientFactory::$preparedEvents[] = $preparedEvent;
            }
        };
    }
}
