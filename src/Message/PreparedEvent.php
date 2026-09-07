<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message;

use Setono\MetaConversionsApi\Event\Event;

/**
 * An event whose payload was already built, so it can be handed to the SDK client as is
 *
 * The payload is computed when the event is raised, i.e. while the request that produced it is still around, and
 * travels through the transport ready to post. This class exists to give that payload back to
 * ClientInterface::sendEvent(), which takes an Event
 *
 * @internal
 */
final class PreparedEvent extends Event
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $eventName, private readonly array $payload)
    {
        parent::__construct($eventName);
    }

    public function getPayload(string $context = self::PAYLOAD_CONTEXT_SERVER): array
    {
        return $this->payload;
    }
}
