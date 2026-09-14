<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\Message\Command\SendEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(DispatchOnCommandBusSubscriber::class)]
final class DispatchOnCommandBusSubscriberTest extends TestCase
{
    #[Test]
    public function it_dispatches_the_command(): void
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);

        $dispatched = [];
        $bus = self::bus(static function (object $message) use (&$dispatched): void {
            $dispatched[] = $message;
        });

        (new DispatchOnCommandBusSubscriber($bus, self::consentChecker(true)))
            ->dispatch(new ConversionsApiEventRaised($metaEvent));

        self::assertCount(1, $dispatched);
        self::assertInstanceOf(SendEvent::class, $dispatched[0]);
        self::assertSame($metaEvent->eventName, $dispatched[0]->eventName);
        self::assertSame($metaEvent->eventId, $dispatched[0]->eventId);
    }

    #[Test]
    public function it_does_not_dispatch_without_consent(): void
    {
        $dispatched = [];
        $bus = self::bus(static function (object $message) use (&$dispatched): void {
            $dispatched[] = $message;
        });

        (new DispatchOnCommandBusSubscriber($bus, self::consentChecker(false)))
            ->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));

        self::assertSame([], $dispatched);
    }

    /**
     * With synchronous handling the http call to Meta happens inside the visitor's request. An expired access
     * token must not take the page down
     */
    #[Test]
    public function it_does_not_let_a_failure_escape_into_the_request(): void
    {
        $bus = self::bus(static function (): void {
            throw new \RuntimeException('Invalid OAuth access token');
        });

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(self::stringContains('could not be sent to Meta'));

        (new DispatchOnCommandBusSubscriber($bus, self::consentChecker(true), $logger))
            ->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)));
    }

    private static function bus(callable $onDispatch): MessageBusInterface
    {
        return new class($onDispatch) implements MessageBusInterface {
            /** @var callable */
            private $onDispatch;

            public function __construct(callable $onDispatch)
            {
                $this->onDispatch = $onDispatch;
            }

            public function dispatch(object $message, array $stamps = []): Envelope
            {
                ($this->onDispatch)($message);

                return new Envelope($message);
            }
        };
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
}
