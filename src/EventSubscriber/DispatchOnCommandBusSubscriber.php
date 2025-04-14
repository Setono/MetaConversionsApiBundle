<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DispatchOnCommandBusSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly ConsentCheckerInterface $consentChecker,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['dispatch', -1000],
        ];
    }

    public function dispatch(ConversionsApiEventRaised $event): void
    {
        if (!$this->consentChecker->isGranted()) {
            return;
        }

        $this->commandBus->dispatch(new SendEvent($event->event));
    }
}
