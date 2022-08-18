<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StopPropagationIfNoPixelsHasBeenAddedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', -950],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if ([] === $event->event->pixels) {
            $event->stopPropagation();
        }
    }
}
