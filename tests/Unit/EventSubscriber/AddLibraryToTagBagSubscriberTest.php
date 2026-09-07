<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Generator\FbqGenerator;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddLibraryToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\Tests\Double\Doubles;
use Setono\TagBag\Renderer\ContentAwareRenderer;
use Setono\TagBag\Tag\TagInterface;
use Setono\TagBag\TagBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(AddLibraryToTagBagSubscriber::class)]
final class AddLibraryToTagBagSubscriberTest extends TestCase
{
    #[Test]
    public function it_renders_the_pixel_and_the_init(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag)->add(self::event());

        self::assertStringContainsString('connect.facebook.net/en_US/fbevents.js', $tagBag->renderSection(TagInterface::SECTION_HEAD));
        self::assertStringContainsString("fbq('init', '1234'", $tagBag->renderAll());
    }

    #[Test]
    public function it_renders_nothing_without_pixels(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag, pixels: [])->add(self::event());

        self::assertSame('', $tagBag->renderAll());
    }

    #[Test]
    public function it_renders_nothing_without_consent(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag, consentGranted: false)->add(self::event());

        self::assertSame('', $tagBag->renderAll());
    }

    #[Test]
    public function it_ignores_sub_requests(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag)->add(self::event(HttpKernelInterface::SUB_REQUEST));

        self::assertSame('', $tagBag->renderAll());
    }

    /**
     * @param list<Pixel>|null $pixels
     */
    private static function subscriber(TagBag $tagBag, bool $consentGranted = true, ?array $pixels = null): AddLibraryToTagBagSubscriber
    {
        return new AddLibraryToTagBagSubscriber(
            $tagBag,
            new FbqGenerator(),
            Doubles::consentChecker($consentGranted),
            Doubles::pixelProvider($pixels ?? [new Pixel('1234', 's3cr3t')]),
        );
    }

    private static function event(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(self::createStub(HttpKernelInterface::class), new Request(), $requestType);
    }

    /**
     * setono/tag-bag ^2.2 requires a renderer while ^2.5 defaults it, and CompositeRenderer takes its renderers
     * through add() in 2.2 and through the constructor in 2.5. Every tag the bundle produces is content aware,
     * so one renderer covers both ends of the supported range
     */
    private static function tagBag(): TagBag
    {
        return new TagBag(new ContentAwareRenderer());
    }
}
