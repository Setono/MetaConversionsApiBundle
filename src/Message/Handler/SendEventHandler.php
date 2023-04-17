<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Message\Handler;

use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;

final class SendEventHandler
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function __invoke(SendEvent $message): void
    {
        $this->client->sendEvent($message->event);
    }
}
