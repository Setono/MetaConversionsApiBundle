<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
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
    use MainRequestTrait;

    private const COOKIE_NAME = '_fbp';

    private FbpContextInterface $fbpContext;

    public function __construct(FbpContextInterface $fbpContext)
    {
        $this->fbpContext = $fbpContext;
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

        $fbp = $this->fbpContext->getFbp();

        if (!$this->setCookie($event->getRequest(), $fbp)) {
            return;
        }

        $cookie = Cookie::create(
            self::COOKIE_NAME,
            $fbp->value(),
            new \DateTimeImmutable('+90 days')
        )
            ->withHttpOnly(false) // we need this to allow the js library to also use the cookie value
        ;

        $event->getResponse()->headers->setCookie($cookie);
    }

    /**
     * Returns true if the cookie should be created/updated
     */
    private function setCookie(Request $request, Fbp $fbp): bool
    {
        if (!$request->cookies->has(self::COOKIE_NAME)) {
            return true;
        }

        // If the creation time of the cookie is more than 2 hours ago, we will renew its expiry date
        // Meta/Facebook does something similar
        return $fbp->getCreationTimeAsSeconds() < (time() - 7200);
    }
}
