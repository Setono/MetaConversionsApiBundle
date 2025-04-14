<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\MetaConversionsApiBundle\Tag\MetaPixelTag;
use Setono\TagBag\Tag\TagInterface;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AddLibraryToTagBagSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TagBagInterface $tagBag,
        private readonly FbqGeneratorInterface $fbqGenerator,
        private readonly ?ConsentContextInterface $consentContext,
        private readonly PixelProviderInterface $pixelProvider,
        private readonly bool $clientSideEnabled,
        private readonly bool $consentEnabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'add',
        ];
    }

    public function add(RequestEvent $event): void
    {
        if (!$this->clientSideEnabled || !$event->isMainRequest()) {
            return;
        }

        if ($this->consentEnabled && null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
            return;
        }

        $pixels = $this->pixelProvider->getPixels();
        if ([] === $pixels) {
            return;
        }

        $this->tagBag->add(
            MetaPixelTag::create()->withPriority(200)->withSection(TagInterface::SECTION_HEAD),
        );

        $this->tagBag->add(
            // the priority for this one has to be lower than the one in \Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber
            // this way this one will be replaced by the other one if it is added to the tag bag (because of the lower priority)
            FbqInitTag::create($this->fbqGenerator->generateInit($pixels), 50),
        );
    }
}
