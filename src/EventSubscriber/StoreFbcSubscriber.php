<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
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
    public function __construct(
        private readonly FbcContextInterface $fbcContext,
        private readonly ConsentCheckerInterface $consentChecker,
        private readonly PixelProviderInterface $pixelProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'store',
        ];
    }

    public function store(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // only store a cookie if the fbclid is set on the current request
        if (!$event->getRequest()->query->has('fbclid')) {
            return;
        }

        $response = $event->getResponse();

        // A redirect is a normal landing for an ad click, e.g. when the application strips the fbclid, so unlike
        // the fbp cookie this one really does have to survive one
        if (!$response->isSuccessful() && !$response->isRedirection()) {
            return;
        }

        // There is no point in remembering a click we have nowhere to send events to
        if ([] === $this->pixelProvider->getPixels()) {
            return;
        }

        if (!$this->consentChecker->isGranted()) {
            return;
        }

        $fbc = $this->fbcContext->getFbc();
        if (null === $fbc) {
            return;
        }

        $response->headers->setCookie(Cookie::create(
            Cookies::FBC,
            $fbc->value(),
            new \DateTimeImmutable(Cookies::LIFETIME),
        )->withHttpOnly(false));  // we need this to allow the js library to also use the cookie value
    }
}
