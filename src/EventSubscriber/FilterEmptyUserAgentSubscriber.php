<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterEmptyUserAgentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', -850],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if (null === $event->event->userData->clientUserAgent || '' === $event->event->userData->clientUserAgent) {
            $event->stopPropagation();
        }
    }
}
