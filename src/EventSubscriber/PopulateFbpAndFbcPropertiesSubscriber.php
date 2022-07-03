<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Context\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Context\FbpContextInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PopulateFbpAndFbcPropertiesSubscriber implements EventSubscriberInterface
{
    private FbpContextInterface $fbpContext;

    private FbcContextInterface $fbcContext;

    public function __construct(FbpContextInterface $fbpContext, FbcContextInterface $fbcContext)
    {
        $this->fbpContext = $fbpContext;
        $this->fbcContext = $fbcContext;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConversionApiEventRaised::class => ['populate', 900],
        ];
    }

    public function populate(ConversionApiEventRaised $event): void
    {
        $event->event->userData->fbp = $this->fbpContext->getFbp();
        $event->event->userData->fbc = $this->fbcContext->getFbc();
    }
}
