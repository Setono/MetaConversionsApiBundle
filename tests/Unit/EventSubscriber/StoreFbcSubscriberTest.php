<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Cookie\CookieDomain;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreFbcSubscriber;
use Setono\MetaConversionsApiBundle\Tests\Double\Doubles;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(StoreFbcSubscriber::class)]
final class StoreFbcSubscriberTest extends TestCase
{
    #[Test]
    public function it_stores_the_click_id(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response());

        self::subscriber()->store($event);

        $cookie = self::cookie($event);
        self::assertNotNull($cookie);
        self::assertStringEndsWith('.IwAR0rmfgHgx', (string) $cookie->getValue());
        // The browser pixel has to be able to read it
        self::assertFalse($cookie->isHttpOnly());
    }

    #[Test]
    public function it_does_nothing_without_a_click_id_on_the_request(): void
    {
        $event = self::event(new Request(), new Response());

        self::subscriber()->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_nothing_without_consent(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response());

        self::subscriber(consentGranted: false)->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_nothing_without_pixels(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response());

        self::subscriber(pixels: [])->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_nothing_when_the_context_has_no_fbc(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response());

        self::subscriber(fbc: null)->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_ignores_sub_requests(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response(), HttpKernelInterface::SUB_REQUEST);

        self::subscriber()->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_nothing_on_an_error_response(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response('', Response::HTTP_NOT_FOUND));

        self::subscriber()->store($event);

        self::assertNull(self::cookie($event));
    }

    /**
     * An ad click frequently lands on a redirect, for instance when the application strips the fbclid, so this one
     * really does have to survive a 302
     */
    #[Test]
    public function it_stores_the_click_id_on_a_redirect(): void
    {
        $event = self::event(new Request(['fbclid' => 'IwAR0rmfgHgx']), new Response('', Response::HTTP_FOUND));

        self::subscriber()->store($event);

        self::assertNotNull(self::cookie($event));
    }

    private static function cookie(ResponseEvent $event): ?Cookie
    {
        foreach ($event->getResponse()->headers->getCookies() as $cookie) {
            if (Cookies::FBC === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * @param list<Pixel>|null $pixels
     */
    private static function subscriber(
        ?Fbc $fbc = new Fbc('IwAR0rmfgHgx'),
        bool $consentGranted = true,
        ?array $pixels = null,
    ): StoreFbcSubscriber {
        return new StoreFbcSubscriber(
            Doubles::fbcContext($fbc),
            Doubles::consentChecker($consentGranted),
            Doubles::pixelProvider($pixels ?? [new Pixel('1234', 's3cr3t')]),
            new CookieDomain(new RequestStack()),
        );
    }

    private static function event(Request $request, Response $response, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent(self::createStub(HttpKernelInterface::class), $request, $requestType, $response);
    }
}
