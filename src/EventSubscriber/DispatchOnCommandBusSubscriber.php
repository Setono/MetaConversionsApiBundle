<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DispatchOnCommandBusSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $commandBus;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionApiEventRaised::class => ['dispatch', -1000],
        ];
    }

    public function dispatch(ConversionApiEventRaised $event): void
    {
        $this->commandBus->dispatch(new SendEvent($event->event));
    }
}
