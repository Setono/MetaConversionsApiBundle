<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
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
            ConversionApiEventRaised::class => ['populate', 700],
        ];
    }

    public function populate(ConversionApiEventRaised $event): void
    {
        $event->event->pixels = $this->pixelProvider->getPixels();
    }
}
