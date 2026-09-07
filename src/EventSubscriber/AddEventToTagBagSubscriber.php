<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Parameters;
use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\TagBag\Tag\ContentTag;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddEventToTagBagSubscriber implements EventSubscriberInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly TagBagInterface $tagBag,
        private readonly FbqGeneratorInterface $fbqGenerator,
        private readonly ConsentCheckerInterface $consentChecker,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['add', ConversionsApiEventRaised::PRIORITY_SEND],
        ];
    }

    public function add(ConversionsApiEventRaised $event): void
    {
        if (!$this->consentChecker->isGranted()) {
            $this->logger->debug('The event {event_name} ({event_id}) was not rendered client side because consent was not granted', [
                'event_name' => $event->event->eventName,
                'event_id' => $event->event->eventId,
            ]);

            return;
        }

        $this->tagBag->add(
            FbqInitTag::create($this->fbqGenerator->generateInit(
                $event->event->pixels,
                $event->event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER),
            ), 100),
        );

        $this->tagBag->add(
            ContentTag::create($this->fbqGenerator->generateTrack($event->event)),
        );
    }
}
