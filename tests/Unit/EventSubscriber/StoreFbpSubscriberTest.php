<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreFbpSubscriber;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(StoreFbpSubscriber::class)]
final class StoreFbpSubscriberTest extends TestCase
{
    #[Test]
    public function it_sets_the_cookie(): void
    {
        $event = self::event(new Request(), new Response());

        self::subscriber()->store($event);

        self::assertNotNull(self::cookie($event));
    }

    #[Test]
    public function it_makes_the_cookie_readable_by_the_browser_pixel(): void
    {
        $event = self::event(new Request(), new Response());

        self::subscriber()->store($event);

        $cookie = self::cookie($event);
        self::assertNotNull($cookie);
        self::assertFalse($cookie->isHttpOnly());
    }

    #[Test]
    public function it_does_not_set_the_cookie_without_pixels(): void
    {
        // A Set-Cookie header makes the response uncacheable, and there is nowhere to send events to anyway
        $event = self::event(new Request(), new Response());

        self::subscriber(pixels: [])->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_not_set_the_cookie_without_consent(): void
    {
        $event = self::event(new Request(), new Response());

        self::subscriber(consentGranted: false)->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_not_set_the_cookie_on_a_sub_request(): void
    {
        $event = self::event(new Request(), new Response(), HttpKernelInterface::SUB_REQUEST);

        self::subscriber()->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_does_not_set_the_cookie_on_an_error_response(): void
    {
        $event = self::event(new Request(), new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));

        self::subscriber()->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_sets_the_cookie_on_a_redirect(): void
    {
        // An ad click regularly lands on a redirect
        $event = self::event(new Request(), new Response('', Response::HTTP_FOUND));

        self::subscriber()->store($event);

        self::assertNotNull(self::cookie($event));
    }

    #[Test]
    public function it_does_not_renew_a_fresh_cookie(): void
    {
        $fbp = Fbp::fromString(sprintf('fb.1.%d000.1088522659', time() - 60));
        $request = new Request([], [], [], [Cookies::FBP => $fbp->value()]);

        $event = self::event($request, new Response());

        self::subscriber(fbp: $fbp)->store($event);

        self::assertNull(self::cookie($event));
    }

    #[Test]
    public function it_renews_a_cookie_older_than_two_hours(): void
    {
        $fbp = Fbp::fromString(sprintf('fb.1.%d000.1088522659', time() - 7300));
        $request = new Request([], [], [], [Cookies::FBP => $fbp->value()]);

        $event = self::event($request, new Response());

        self::subscriber(fbp: $fbp)->store($event);

        self::assertNotNull(self::cookie($event));
    }

    private static function cookie(ResponseEvent $event): ?Cookie
    {
        foreach ($event->getResponse()->headers->getCookies() as $cookie) {
            if (Cookies::FBP === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * @param list<Pixel>|null $pixels
     */
    private static function subscriber(?Fbp $fbp = null, bool $consentGranted = true, ?array $pixels = null): StoreFbpSubscriber
    {
        return new StoreFbpSubscriber(
            new class($fbp ?? new Fbp()) implements FbpContextInterface {
                public function __construct(private readonly Fbp $fbp)
                {
                }

                public function getFbp(): Fbp
                {
                    return $this->fbp;
                }
            },
            new class($consentGranted) implements ConsentCheckerInterface {
                public function __construct(private readonly bool $granted)
                {
                }

                public function isGranted(): bool
                {
                    return $this->granted;
                }
            },
            new class($pixels ?? [new Pixel('1234', 's3cr3t')]) implements PixelProviderInterface {
                /**
                 * @param list<Pixel> $pixels
                 */
                public function __construct(private readonly array $pixels)
                {
                }

                public function getPixels(): array
                {
                    return $this->pixels;
                }
            },
        );
    }

    private static function event(Request $request, Response $response, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent(self::createStub(HttpKernelInterface::class), $request, $requestType, $response);
    }
}
