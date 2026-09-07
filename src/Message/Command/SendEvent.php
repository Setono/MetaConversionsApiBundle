<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Command;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

/**
 * Send a conversions api event to Meta/Facebook
 *
 * This deliberately carries the finished payload rather than the Event object. When the command is routed to a
 * transport it is written to that transport's storage, and to the failure transport when it fails, so it must not
 * carry anything that does not belong there:
 *
 * - The payload is already normalized and hashed by the SDK, so no raw email addresses or phone numbers are stored.
 * - Only pixel ids travel. The access tokens are resolved when the event is sent, by an AccessTokenResolverInterface.
 *
 * As a side effect everything in here is a scalar or an array, so the message also survives the Symfony serializer
 */
final class SendEvent implements CommandInterface
{
    /**
     * @param array<string, mixed> $payload The normalized and hashed payload, ready to be posted
     * @param list<string> $pixelIds
     */
    public function __construct(
        public readonly string $eventName,
        public readonly string $eventId,
        public readonly array $payload,
        public readonly array $pixelIds,
        public readonly ?string $testEventCode = null,
    ) {
    }

    public static function fromEvent(Event $event): self
    {
        return new self(
            $event->eventName,
            $event->eventId,
            $event->getPayload(),
            array_map(static fn (Pixel $pixel): string => $pixel->id, $event->pixels),
            $event->testEventCode,
        );
    }
}
