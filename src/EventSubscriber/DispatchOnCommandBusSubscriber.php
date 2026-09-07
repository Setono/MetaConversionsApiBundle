<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DispatchOnCommandBusSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly ConsentCheckerInterface $consentChecker,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['dispatch', ConversionsApiEventRaised::PRIORITY_SEND],
        ];
    }

    public function dispatch(ConversionsApiEventRaised $event): void
    {
        if (!$this->consentChecker->isGranted()) {
            $this->logger->debug('The event {event_name} ({event_id}) was not sent server side because consent was not granted', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
            ]);

            return;
        }

        $this->commandBus->dispatch(new SendEvent($event->event));
    }
}
