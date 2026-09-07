<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\TestEventCode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class PopulateTestEventCodePropertySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        /** A static test event code applied to every event, configured with test_event_code.value */
        private readonly ?string $testEventCode = null,
        /** Whether the test event code stored by StoreTestEventCodeSubscriber should be applied */
        private readonly bool $readFromSession = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['populate', 800],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        if (null !== $this->testEventCode) {
            $event->event->testEventCode = $this->testEventCode;

            return;
        }

        if (!$this->readFromSession) {
            return;
        }

        $request = $this->requestStack->getMainRequest();

        // Reading from a session that has not been started yet starts it. That would give every anonymous visitor a
        // session cookie and make the response uncacheable for shared caches, so we only read when the visitor
        // already has a session. hasPreviousSession() checks the session cookie without starting anything
        if (null === $request || !$request->hasPreviousSession()) {
            return;
        }

        $testEventCode = $request->getSession()->get(TestEventCode::SESSION_KEY);
        if (!is_string($testEventCode) || '' === $testEventCode) {
            return;
        }

        $event->event->testEventCode = $testEventCode;
    }
}
