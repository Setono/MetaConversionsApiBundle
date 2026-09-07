<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FilterEmptyUserAgentSubscriber implements EventSubscriberInterface
{
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
            $event->stopPropagation();
        }
    }
}
