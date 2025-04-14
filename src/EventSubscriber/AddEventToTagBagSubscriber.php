<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApi\Event\Parameters;
use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\TagBag\Tag\ContentTag;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddEventToTagBagSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TagBagInterface $tagBag,
        private readonly FbqGeneratorInterface $fbqGenerator,
        private readonly ?ConsentContextInterface $consentContext,
        private readonly bool $clientSideEnabled,
        private readonly bool $consentEnabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['add', -1000],
        ];
    }

    public function add(ConversionsApiEventRaised $event): void
    {
        if (!$this->clientSideEnabled) {
            return;
        }

        if ($this->consentEnabled && null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
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
