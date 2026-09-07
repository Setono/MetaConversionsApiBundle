<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class PopulateTestEventCodePropertySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['populate', 800],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        $request = $this->requestStack->getMainRequest();

        // Reading from a session that has not been started yet starts it. That would give every anonymous visitor a
        // session cookie and make the response uncacheable for shared caches, so we only read when the visitor
        // already has a session. hasPreviousSession() checks the session cookie without starting anything
        if (null === $request || !$request->hasPreviousSession()) {
            return;
        }

        $testEventCode = $request->getSession()->get('smca_test_event_code');
        if (!is_string($testEventCode)) {
            return;
        }

        $event->event->testEventCode = $testEventCode;
    }
}
