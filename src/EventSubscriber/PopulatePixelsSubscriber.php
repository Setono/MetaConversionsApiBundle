<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PopulatePixelsSubscriber implements EventSubscriberInterface
{
    private PixelProviderInterface $pixelProvider;

    public function __construct(PixelProviderInterface $pixelProvider)
    {
        $this->pixelProvider = $pixelProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['populate', 700],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        $event->event->pixels = $this->pixelProvider->getPixels();
    }
}
