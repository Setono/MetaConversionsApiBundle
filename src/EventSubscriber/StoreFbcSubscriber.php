<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApiBundle\Context\FbcContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber will store the fbclid (facebook click id) inside the fbc cookie
 *
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/#fbc
 */
final class StoreFbcSubscriber implements EventSubscriberInterface
{
    use MainRequestTrait;

    private FbcContextInterface $fbcContext;

    public function __construct(FbcContextInterface $fbcContext)
    {
        $this->fbcContext = $fbcContext;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'store',
        ];
    }

    public function store(ResponseEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        // only store a cookie if the fbclid is set on the current request
        if (!$event->getRequest()->query->has('fbclid')) {
            return;
        }

        $fbc = $this->fbcContext->getFbc();
        if (null === $fbc) {
            return;
        }

        $event->getResponse()->headers->setCookie(Cookie::create('_fbc', $fbc, new \DateTimeImmutable('+90 days')));
    }
}
