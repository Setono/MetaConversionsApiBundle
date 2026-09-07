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

        try {
            $this->commandBus->dispatch(new SendEvent($event->event));
        } catch (\Throwable $e) {
            // Tracking must never take the page down. Two things can throw here:
            //
            // 1. The command is handled synchronously, i.e. it is not routed to a transport, and Meta answered with
            //    an error. An expired access token would otherwise break every page that raises an event.
            // 2. The command is routed to a transport and the transport itself is unavailable.
            //
            // Neither is reachable once the command is routed to a working transport, so a routed setup keeps
            // Messenger's retry and failure handling untouched
            $this->logger->error('The event {event_name} ({event_id}) could not be sent to Meta: {message}', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
