<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Command;

use Setono\MetaConversionsApi\Event\Event;

/**
 * Send a conversions api event to Meta/Facebook
 */
final class SendEvent implements CommandInterface
{
    public Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
