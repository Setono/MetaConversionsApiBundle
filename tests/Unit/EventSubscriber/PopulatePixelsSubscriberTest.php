<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulatePixelsSubscriber;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;

#[CoversClass(PopulatePixelsSubscriber::class)]
final class PopulatePixelsSubscriberTest extends TestCase
{
    #[Test]
    public function it_populates_the_pixels_from_the_provider(): void
    {
        $pixels = [new Pixel('1234', 's3cr3t')];

        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new PopulatePixelsSubscriber(new class($pixels) implements PixelProviderInterface {
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
        }))->populate($event);

        self::assertSame($pixels, $event->event->pixels);
    }
}
