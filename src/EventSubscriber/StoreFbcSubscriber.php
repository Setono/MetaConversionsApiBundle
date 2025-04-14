<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\EventSubscriber;

use Setono\Consent\Context\ConsentContextInterface;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
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
        private readonly ?ConsentContextInterface $consentContext = null,
        private readonly ?bool $consentEnabled = null,
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

        if (true === $this->consentEnabled && null !== $this->consentContext && !$this->consentContext->getConsent()->isMarketingConsentGranted()) {
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

        $event->getResponse()->headers->setCookie(Cookie::create(
            '_fbc',
            $fbc->value(),
            new \DateTimeImmutable('+90 days'),
        )->withHttpOnly(false));  // we need this to allow the js library to also use the cookie value
    }
}
