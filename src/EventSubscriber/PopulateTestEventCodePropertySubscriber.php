<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class PopulateTestEventCodePropertySubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionApiEventRaised::class => ['populate', 800],
        ];
    }

    public function populate(ConversionApiEventRaised $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $this->requestStack->getSession();

        $testEventCode = $session->get('smca_test_event_code');
        if (!is_string($testEventCode)) {
            return;
        }

        $event->event->testEventCode = $testEventCode;
    }
}
