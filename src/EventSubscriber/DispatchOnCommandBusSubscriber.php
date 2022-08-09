<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class DispatchOnCommandBusSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $commandBus;

    private ?ConsentContextInterface $consentContext;

    private bool $serverSideEnabled;

    private bool $consentEnabled;

    public function __construct(
        MessageBusInterface $commandBus,
        ?ConsentContextInterface $consentContext,
        bool $serverSideEnabled,
        bool $consentEnabled
    ) {
        $this->commandBus = $commandBus;
        $this->consentContext = $consentContext;
        $this->serverSideEnabled = $serverSideEnabled;
        $this->consentEnabled = $consentEnabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['dispatch', -1000],
        ];
    }

    public function dispatch(ConversionsApiEventRaised $event): void
    {
        if (!$this->serverSideEnabled) {
            return;
        }

        if ($this->consentEnabled && null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
            return;
        }

        $this->commandBus->dispatch(new SendEvent($event->event));
    }
}
