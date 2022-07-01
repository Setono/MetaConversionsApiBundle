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

    /** @var array<string, mixed> */
    public array $context;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(Event $event, array $context = [])
    {
        $this->event = $event;
        $this->context = $context;
    }

    /**
     * @psalm-assert-if-true mixed $this->context[$key]
     */
    public function hasContext(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }
}
