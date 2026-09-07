<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This subscriber will store the fbp information inside the _fbp cookie
 *
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/#fbp
 */
final class StoreFbpSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FbpContextInterface $fbpContext,
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

        $response = $event->getResponse();

        // Any Set-Cookie header makes a response uncacheable for shared caches, so it is only worth writing on a
        // page the visitor actually landed on. Redirects are included because an ad click often lands on one
        if (!$response->isSuccessful() && !$response->isRedirection()) {
            return;
        }

        // There is no point in identifying a browser we have nowhere to send events to
        if ([] === $this->pixelProvider->getPixels()) {
            return;
        }

        if (!$this->consentChecker->isGranted()) {
            return;
        }

        $fbp = $this->fbpContext->getFbp();

        if (!$this->shouldSetCookie($event->getRequest(), $fbp)) {
            return;
        }

        $response->headers->setCookie(Cookie::create(
            Cookies::FBP,
            $fbp->value(),
            new \DateTimeImmutable(Cookies::LIFETIME),
        )->withHttpOnly(false)); // we need this to allow the js library to also use the cookie value
    }

    /**
     * Returns true if the cookie should be created/updated
     */
    private function shouldSetCookie(Request $request, Fbp $fbp): bool
    {
        if (!$request->cookies->has(Cookies::FBP)) {
            return true;
        }

        // If the creation time of the cookie is more than 2 hours ago, we will renew its expiry date
        // Meta/Facebook does something similar
        return $fbp->getCreationTimeAsSeconds() < (time() - 7200);
    }
}
