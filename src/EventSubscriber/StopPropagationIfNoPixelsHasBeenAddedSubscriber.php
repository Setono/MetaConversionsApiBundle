<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StopPropagationIfNoPixelsHasBeenAddedSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', ConversionsApiEventRaised::PRIORITY_SEND + 50],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        if ([] === $event->event->pixels) {
            $this->logger->debug('The event {event_name} ({event_id}) was dropped because no pixels are associated with it. Did you configure any?', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
            ]);

            $event->stopPropagation();
        }
    }
}
