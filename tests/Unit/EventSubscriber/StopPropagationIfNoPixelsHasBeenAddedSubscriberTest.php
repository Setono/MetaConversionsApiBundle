<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\StopPropagationIfNoPixelsHasBeenAddedSubscriber;

#[CoversClass(StopPropagationIfNoPixelsHasBeenAddedSubscriber::class)]
final class StopPropagationIfNoPixelsHasBeenAddedSubscriberTest extends TestCase
{
    #[Test]
    public function it_stops_when_no_pixels_have_been_added(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        (new StopPropagationIfNoPixelsHasBeenAddedSubscriber())->filter($event);

        self::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function it_does_not_stop_when_a_pixel_has_been_added(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->pixels = [new Pixel('1234', 's3cr3t')];

        $event = new ConversionsApiEventRaised($metaEvent);

        (new StopPropagationIfNoPixelsHasBeenAddedSubscriber())->filter($event);

        self::assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function it_logs_why_the_event_was_dropped(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('no pixels'));

        (new StopPropagationIfNoPixelsHasBeenAddedSubscriber($logger))->filter(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }
}
