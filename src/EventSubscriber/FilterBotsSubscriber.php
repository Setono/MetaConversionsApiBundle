<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\BotDetectionBundle\BotDetector\BotDetectorInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterBotsSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly BotDetectorInterface $botDetector,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
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
            $this->logger->debug('The event {event_name} ({event_id}) was dropped because the request comes from a bot: {user_agent}', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
                'user_agent' => $event->event->userData->clientUserAgent,
            ]);

            $event->stopPropagation();
        }
    }
}
