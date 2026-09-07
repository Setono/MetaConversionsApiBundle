<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Event;

use Setono\MetaConversionsApi\Event\Event;
use Symfony\Contracts\EventDispatcher\Event as StoppableEvent;

/**
 * Dispatch this event onto the EventDispatcher and everything will be handled for you
 */
final class ConversionsApiEventRaised extends StoppableEvent
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(public Event $event, public array $context = [])
    {
    }

    public function hasContext(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }
}
