<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
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

        // only store the cookie if the cookie hasn't been set before
        if ($event->getRequest()->cookies->has(self::COOKIE_NAME)) {
            return;
        }

        $cookie = Cookie::create(self::COOKIE_NAME, $this->fbpContext->getFbp(), new \DateTimeImmutable('+90 days'))
            ->withHttpOnly(false)
        ;

        $event->getResponse()->headers->setCookie($cookie);
    }
}
