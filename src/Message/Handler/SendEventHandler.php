<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\AccessTokenResolver\AccessTokenResolverInterface;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Setono\MetaConversionsApiBundle\Message\PreparedEvent;

final class SendEventHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly AccessTokenResolverInterface $accessTokenResolver,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(SendEvent $message): void
    {
        $pixels = [];

        foreach ($message->pixelIds as $pixelId) {
            $accessToken = $this->accessTokenResolver->resolve($pixelId);

            // A pixel without an access token cannot be used server side: Meta answers 400, the SDK throws, and
            // Messenger retries the message until it ends up in the failure transport. One warning is more useful.
            // Client side tracking is unaffected, because rendering fbq() calls only needs the pixel id
            if (null === $accessToken) {
                $this->logger->warning('The pixel {pixel} has no access token, so the event {event_name} ({event_id}) was not sent to it', [
                    'pixel' => $pixelId,
                    'event_name' => $message->eventName,
                    'event_id' => $message->eventId,
                ]);

                continue;
            }

            $pixels[] = new Pixel($pixelId, $accessToken);
        }

        if ([] === $pixels) {
            return;
        }

        $event = new PreparedEvent($message->eventName, $message->payload);
        $event->pixels = $pixels;
        $event->testEventCode = $message->testEventCode;

        $this->client->sendEvent($event);
    }
}
