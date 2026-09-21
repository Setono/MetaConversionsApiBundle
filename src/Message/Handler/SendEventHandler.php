<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Handler;

use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Exception\InvalidArgumentException;
use Setono\MetaConversionsApi\Exception\ResponseException;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class SendEventHandler
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly PixelProviderInterface $pixelProvider,
    ) {
    }

    public function __invoke(SendEvent $message): void
    {
        // The access tokens never travel with the message, so they are added back here. A pixel that still has none
        // afterwards, one that is only used client side for instance, is skipped by the SDK client, which logs it
        $preparedEvent = $message->preparedEvent->withAccessTokens($this->accessTokens());

        // The SDK throws one exception per thing that can be done about a failure, which maps onto Messenger directly.
        // A TransportException is deliberately not caught: the request never got a response, so a retry may succeed
        try {
            $this->client->sendPreparedEvent($preparedEvent);
        } catch (InvalidArgumentException $e) {
            // The input is wrong, e.g. none of the pixels has an access token or the payload cannot be encoded as JSON,
            // and it will be just as wrong on the next attempt
            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        } catch (ResponseException $e) {
            // A server error, or an error Meta itself flags as transient, may go away. Anything else, an invalid access
            // token for instance, is rejected again however many times Messenger retries
            if ($e->statusCode >= 500 || true === $e->errorResponse?->transient) {
                throw $e;
            }

            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<array-key, string> the access tokens indexed by pixel id
     */
    private function accessTokens(): array
    {
        $accessTokens = [];

        foreach ($this->pixelProvider->getPixels() as $pixel) {
            if (null !== $pixel->accessToken) {
                $accessTokens[$pixel->id] = $pixel->accessToken;
            }
        }

        return $accessTokens;
    }
}
