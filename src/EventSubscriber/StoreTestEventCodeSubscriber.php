<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MainRequestTrait\MainRequestTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class StoreTestEventCodeSubscriber implements EventSubscriberInterface
{
    use MainRequestTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'store',
        ];
    }

    public function store(RequestEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        $testEventCode = $request->query->get('_testEventCode') ?? $request->query->get('_test_event_code');
        if (!is_string($testEventCode)) {
            return;
        }

        $request->getSession()->set('smca_test_event_code', $testEventCode);
    }
}
