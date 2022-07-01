<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Client;

use Setono\MetaConversionsApi\Event\Event;

final class Client implements ClientInterface
{
    public function __construct()
    {
    }

    public function sendEvent(Event $event): void
    {
    }
}
