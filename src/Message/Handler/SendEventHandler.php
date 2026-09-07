<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;

final class SendEventHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ClientInterface $client,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(SendEvent $message): void
    {
        $event = $message->event;

        // A pixel without an access token cannot be used server side: Meta answers 400, the SDK throws, and
        // Messenger retries the message until it ends up in the failure transport. One warning is more useful.
        // Client side tracking is unaffected, because rendering fbq() calls only needs the pixel id
        $pixels = array_values(array_filter(
            $event->pixels,
            fn (Pixel $pixel): bool => $this->hasAccessToken($pixel, $event),
        ));

        if ([] === $pixels) {
            return;
        }

        // Cloned so the event the application still holds is not mutated when the command is handled synchronously
        $event = clone $event;
        $event->pixels = $pixels;

        $this->client->sendEvent($event);
    }

    private function hasAccessToken(Pixel $pixel, Event $event): bool
    {
        if (null !== $pixel->accessToken) {
            return true;
        }

        $this->logger->warning('The pixel {pixel} has no access token, so the event {event_name} ({event_id}) was not sent to it', [
            'pixel' => $pixel->id,
            'event_name' => $event->eventName,
            'event_id' => $event->eventId,
        ]);

        return false;
    }
}
