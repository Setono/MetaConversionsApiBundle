<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
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
            ConversionsApiEventRaised::class => ['populate', 900],
        ];
    }

    public function populate(ConversionsApiEventRaised $event): void
    {
        $event->event->userData->fbp = $this->fbpContext->getFbp();
        $event->event->userData->fbc = $this->fbcContext->getFbc();
    }
}
