<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Command;

use Setono\MetaConversionsApi\Event\Event;

/**
 * Send a conversion api event to Meta/Facebook
 */
final class SendEvent
{
    public Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
