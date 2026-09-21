<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Command;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;

/**
 * Send a conversions api event to Meta/Facebook
 *
 * This deliberately carries the SDK's PreparedEvent rather than the Event object. When the command is routed to a
 * transport it is written to that transport's storage, and to the failure transport when it fails, so it must not
 * carry anything that does not belong there:
 *
 * - The payload of a prepared event is already normalized and hashed, so no raw email addresses or phone numbers are
 *   stored.
 * - The access tokens are stripped in the constructor and added back when the event is sent, from the
 *   PixelProviderInterface.
 */
final class SendEvent implements CommandInterface
{
    public readonly PreparedEvent $preparedEvent;

    public function __construct(PreparedEvent $preparedEvent)
    {
        // Event::prepare() keeps the access tokens on the pixels. Stripping them here, rather than trusting every
        // caller to have done it, means there is no way to put an access token on the transport
        $this->preparedEvent = $preparedEvent->withoutAccessTokens();
    }

    public static function fromEvent(Event $event): self
    {
        return new self($event->prepare());
    }
}
