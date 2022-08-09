<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class PopulateRequestPropertiesSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['populate', 1000],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        $event->event->eventSourceUrl = $request->getUri();
        $event->event->userData->clientIpAddress = $request->getClientIp();
        $event->event->userData->clientUserAgent = $request->headers->get('User-Agent');
    }
}
