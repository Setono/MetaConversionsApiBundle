<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Event;

use Setono\MetaConversionsApi\Event\Event;
use Symfony\Contracts\EventDispatcher\Event as StoppableEvent;

/**
 * Dispatch this event onto the EventDispatcher and everything will be handled for you
 *
 * The bundle's own listeners run in four bands. Use the constants below to position your own listener relative to
 * them instead of hard coding a number:
 *
 * | Priority                       | What happens                                                            |
 * |--------------------------------|-------------------------------------------------------------------------|
 * | PRIORITY_POPULATE (and below)  | The bundle fills in request properties, fbp/fbc, test event code, pixels |
 * | PRIORITY_FILTER                | The bundle drops events it should not track (bots, filtered user agents) |
 * | PRIORITY_ENRICH                | Your listeners add user data and custom data                            |
 * | PRIORITY_SEND                  | The bundle renders the client side tags and dispatches the command       |
 *
 * Filtering happens before PRIORITY_ENRICH so that the work your listeners do is not spent on traffic that is
 * discarded anyway. A listener below PRIORITY_ENRICH may never run, because propagation can already be stopped
 */
final class ConversionsApiEventRaised extends StoppableEvent
{
    /**
     * The bundle populates the event from the current request at this priority and just below it
     */
    public const PRIORITY_POPULATE = 1000;

    /**
     * The bundle decides here whether the event should be tracked at all. This runs before PRIORITY_ENRICH so
     * that enrichment is not performed for bots and other traffic that is discarded anyway
     */
    public const PRIORITY_FILTER = 600;

    /**
     * The priority your own listeners should use. Everything the bundle knows about the request is populated by
     * now, traffic the bundle does not want to track has already been discarded, and nothing has been sent yet.
     * This is the default priority of an event listener
     */
    public const PRIORITY_ENRICH = 0;

    /**
     * The bundle hands the event to the tag bag and the command bus at this priority
     */
    public const PRIORITY_SEND = -1000;

    /**
     * @param Event $event The event that will be rendered client side and sent server side. Listeners are expected
     *                     to mutate it, which is how enrichment works
     * @param array<string, mixed> $context Anything your own listeners need but that must not be sent to Meta, for
     *                                      instance the order or the customer the event was raised for. The bundle
     *                                      never reads it
     */
    public function __construct(public readonly Event $event, public readonly array $context = [])
    {
    }

    public function hasContext(string $key): bool
    {
        return array_key_exists($key, $this->context);
    }

    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
