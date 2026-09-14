<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateFbpAndFbcPropertiesSubscriber;

#[CoversClass(PopulateFbpAndFbcPropertiesSubscriber::class)]
final class PopulateFbpAndFbcPropertiesSubscriberTest extends TestCase
{
    #[Test]
    public function it_populates_fbp_and_fbc(): void
    {
        $fbp = new Fbp();
        $fbc = new Fbc('IwAR0rmfgHgx');

        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        self::subscriber($fbp, $fbc)->populate($event);

        self::assertSame($fbp, $event->event->userData->fbp);
        self::assertSame($fbc, $event->event->userData->fbc);
    }

    #[Test]
    public function it_leaves_fbc_null_when_there_is_no_click_id(): void
    {
        $event = new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT));

        self::subscriber(new Fbp(), null)->populate($event);

        self::assertNull($event->event->userData->fbc);
    }

    private static function subscriber(Fbp $fbp, ?Fbc $fbc): PopulateFbpAndFbcPropertiesSubscriber
    {
        return new PopulateFbpAndFbcPropertiesSubscriber(
            new class($fbp) implements FbpContextInterface {
                public function __construct(private readonly Fbp $fbp)
                {
                }

                public function getFbp(): Fbp
                {
                    return $this->fbp;
                }
            },
            new class($fbc) implements FbcContextInterface {
                public function __construct(private readonly ?Fbc $fbc)
                {
                }

                public function getFbc(): ?Fbc
                {
                    return $this->fbc;
                }
            },
        );
    }
}
