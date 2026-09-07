<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
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
            ConversionsApiEventRaised::class => ['filter', -900],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if ($this->botDetector->isBotRequest()) {
            $event->stopPropagation();
        }
    }
}
