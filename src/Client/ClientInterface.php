<?php
declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Client;

use Setono\MetaConversionsApi\Event\Event;

interface ClientInterface
{
    public function sendEvent(Event $event): void;
}
