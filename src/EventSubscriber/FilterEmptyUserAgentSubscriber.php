<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterEmptyUserAgentSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['filter', ConversionsApiEventRaised::PRIORITY_FILTER + 50],
        ];
    }

    public function filter(ConversionsApiEventRaised $event): void
    {
        // Meta only expects a client user agent for website events. An event raised from a console command, a
        // message handler or a webhook legitimately has none, and dropping those would make the other action
        // sources the SDK supports unusable
        if (Event::ACTION_SOURCE_WEBSITE !== $event->event->actionSource) {
            return;
        }

        $userAgent = $event->event->userData->clientUserAgent;
        if (null === $userAgent || '' === $userAgent) {
            $this->logger->debug('The event {event_name} ({event_id}) was dropped because the request has no user agent', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
            ]);

            $event->stopPropagation();
        }
    }
}
