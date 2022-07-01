<?php
declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Event;

use Setono\MetaConversionsApi\Event\Event;
use Symfony\Contracts\EventDispatcher\Event as StoppableEvent;

/**
 * Dispatch this event onto the EventDispatcher and everything will be handled for you
 */
final class ConversionApiEventRaised extends StoppableEvent
{
    public Event $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
