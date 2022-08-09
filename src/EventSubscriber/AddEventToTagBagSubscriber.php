<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApi\Event\Parameters;
use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\TagBag\Tag\ContentTag;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddEventToTagBagSubscriber implements EventSubscriberInterface
{
    private ?TagBagInterface $tagBag;

    private FbqGeneratorInterface $fbqGenerator;

    private ?ConsentContextInterface $consentContext;

    private bool $clientSideEnabled;

    private bool $consentEnabled;

    public function __construct(
        ?TagBagInterface $tagBag,
        FbqGeneratorInterface $fbqGenerator,
        ?ConsentContextInterface $consentContext,
        bool $clientSideEnabled,
        bool $consentEnabled
    ) {
        $this->tagBag = $tagBag;
        $this->fbqGenerator = $fbqGenerator;
        $this->consentContext = $consentContext;
        $this->clientSideEnabled = $clientSideEnabled;
        $this->consentEnabled = $consentEnabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionApiEventRaised::class => ['add', -1000],
        ];
    }

    public function add(ConversionApiEventRaised $event): void
    {
        if (!$this->clientSideEnabled || null === $this->tagBag) {
            return;
        }

        if ($this->consentEnabled && null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
            return;
        }

        $this->tagBag->add(
            FbqInitTag::create($this->fbqGenerator->generateInit(
                $event->event->pixels,
                $event->event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER)
            ), 100)
        );

        $this->tagBag->add(
            ContentTag::create($this->fbqGenerator->generateTrack($event->event))
        );
    }
}
