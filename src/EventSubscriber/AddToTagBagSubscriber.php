<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\TagBag\Tag\ContentAwareTag;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddToTagBagSubscriber implements EventSubscriberInterface
{
    private ?TagBagInterface $tagBag;

    private FbqGeneratorInterface $fbqGenerator;

    private ?ConsentContextInterface $consentContext;

    private bool $clientSideEnabled;

    public function __construct(
        ?TagBagInterface $tagBag,
        FbqGeneratorInterface $fbqGenerator,
        ?ConsentContextInterface $consentContext,
        bool $clientSideEnabled
    ) {
        $this->tagBag = $tagBag;
        $this->fbqGenerator = $fbqGenerator;
        $this->consentContext = $consentContext;
        $this->clientSideEnabled = $clientSideEnabled;
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

        if (null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
            return;
        }

        $this->tagBag->add(
            ContentAwareTag::create($this->fbqGenerator->generateInit($event->event, true))
                ->withPriority(100)
        );

        $this->tagBag->add(
            ContentAwareTag::create($this->fbqGenerator->generateTrack($event->event, true))
        );
    }
}
