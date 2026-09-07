<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Generator\FbqGenerator;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddLibraryToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Setono\TagBag\Renderer\ContentAwareRenderer;
use Setono\TagBag\TagBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Three listeners return early when consent is not granted, and each says so at debug level. Without that, a site
 * where consent is never granted looks exactly like a broken integration
 */
#[CoversClass(AddEventToTagBagSubscriber::class)]
#[CoversClass(AddLibraryToTagBagSubscriber::class)]
#[CoversClass(DispatchOnCommandBusSubscriber::class)]
final class ConsentGateLoggingTest extends TestCase
{
    #[Test]
    public function the_event_tags_say_why_they_were_not_rendered(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('consent'));

        $tagBag = self::tagBag();

        (new AddEventToTagBagSubscriber($tagBag, new FbqGenerator(), self::consentChecker(false), $logger))
            ->add(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));

        self::assertSame('', $tagBag->renderAll());
    }

    #[Test]
    public function the_library_says_why_it_was_not_rendered(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('consent'));

        $tagBag = self::tagBag();

        (new AddLibraryToTagBagSubscriber($tagBag, new FbqGenerator(), self::consentChecker(false), self::pixelProvider([new Pixel('1234')]), $logger))
            ->add(self::requestEvent());

        self::assertSame('', $tagBag->renderAll());
    }

    #[Test]
    public function the_library_says_when_there_are_no_pixels(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('no pixels'));

        $tagBag = self::tagBag();

        (new AddLibraryToTagBagSubscriber($tagBag, new FbqGenerator(), self::consentChecker(true), self::pixelProvider([]), $logger))
            ->add(self::requestEvent());

        self::assertSame('', $tagBag->renderAll());
    }

    #[Test]
    public function the_command_says_why_it_was_not_dispatched(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('consent'));

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        (new DispatchOnCommandBusSubscriber($bus, self::consentChecker(false), $logger))
            ->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }

    #[Test]
    public function it_dispatches_when_consent_is_granted(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->willReturnCallback(
            static fn (object $message): Envelope => new Envelope($message),
        );

        (new DispatchOnCommandBusSubscriber($bus, self::consentChecker(true)))
            ->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }

    /**
     * setono/tag-bag ^2.2 requires a renderer while ^2.5 defaults it. Every tag the bundle produces is content
     * aware, so one renderer covers both ends of the supported range
     */
    private static function tagBag(): TagBag
    {
        return new TagBag(new ContentAwareRenderer());
    }

    private static function requestEvent(): RequestEvent
    {
        return new RequestEvent(self::createStub(HttpKernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST);
    }

    private static function consentChecker(bool $granted): ConsentCheckerInterface
    {
        return new class($granted) implements ConsentCheckerInterface {
            public function __construct(private readonly bool $granted)
            {
            }

            public function isGranted(): bool
            {
                return $this->granted;
            }
        };
    }

    /**
     * @param list<Pixel> $pixels
     */
    private static function pixelProvider(array $pixels): PixelProviderInterface
    {
        return new class($pixels) implements PixelProviderInterface {
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
        };
    }
}
