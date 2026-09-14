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
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

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
        // A message serialised by 0.1.x carries an Event object instead of a payload. Messenger's PhpSerializer
        // decodes it without complaint because the class still exists, but every property of the current shape is
        // left uninitialised. Retrying cannot help, and the old body still holds the access token and the raw
        // personal data this shape was introduced to keep out of the transport, so fail fast: the message goes to
        // the failure transport with a reason instead of through three retries first. See UPGRADE.md
        if (!(new \ReflectionProperty($message, 'pixelIds'))->isInitialized($message)) {
            throw new UnrecoverableMessageHandlingException('This SendEvent was serialised by a previous release of the bundle and cannot be handled. Drain the transport on that release before deploying, see UPGRADE.md');
        }

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
            // The message only exists because server side tracking is enabled, so an event that can be sent to
            // none of its pixels is a misconfiguration rather than a per-pixel detail. The usual cause is a custom
            // PixelProviderInterface, whose tokens never leave the request, without a matching
            // AccessTokenResolverInterface. A stock production Monolog setup buffers a warning away; it keeps an error
            $this->logger->error('The event {event_name} ({event_id}) was not sent because none of its pixels ({pixels}) has an access token. If your pixels come from your own PixelProviderInterface, alias AccessTokenResolverInterface as well', [
                'event_name' => $message->eventName,
                'event_id' => $message->eventId,
                'pixels' => implode(', ', $message->pixelIds),
            ]);

            return;
        }

        $event = new PreparedEvent($message->eventName, $message->payload);
        $event->pixels = $pixels;
        $event->testEventCode = $message->testEventCode;

        $this->client->sendEvent($event);
    }
}
