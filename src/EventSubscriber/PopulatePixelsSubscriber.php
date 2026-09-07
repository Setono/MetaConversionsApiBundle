<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PopulatePixelsSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly PixelProviderInterface $pixelProvider)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionsApiEventRaised::class => ['populate', ConversionsApiEventRaised::PRIORITY_POPULATE - 300],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        $event->event->pixels = $this->pixelProvider->getPixels();
    }
}
