<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterBotsSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly BotDetectorInterface $botDetector)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', ConversionsApiEventRaised::PRIORITY_FILTER],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        // A bot check is about the visitor behind the current request, which says nothing about an event raised
        // from a console command or a message handler
        if (Event::ACTION_SOURCE_WEBSITE !== $event->event->actionSource) {
            return;
        }

        if ($this->botDetector->isBotRequest()) {
            $event->stopPropagation();
        }
    }
}
