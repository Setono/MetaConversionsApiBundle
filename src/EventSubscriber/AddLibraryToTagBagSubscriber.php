<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApi\Generator\FbqGeneratorInterface;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\MetaConversionsApiBundle\Tag\MetaPixelTag;
use Setono\TagBag\Tag\TagInterface;
use Setono\TagBag\TagBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AddLibraryToTagBagSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TagBagInterface $tagBag,
        private FbqGeneratorInterface $fbqGenerator,
        private ConsentCheckerInterface $consentChecker,
        private PixelProviderInterface $pixelProvider,
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
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->consentChecker->isGranted()) {
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
