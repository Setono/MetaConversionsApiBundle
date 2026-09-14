<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Generator\FbqGenerator;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\Tag\FbqInitTag;
use Setono\MetaConversionsApiBundle\Tests\Double\Doubles;
use Setono\TagBag\Renderer\ContentAwareRenderer;
use Setono\TagBag\TagBag;

#[CoversClass(AddEventToTagBagSubscriber::class)]
final class AddEventToTagBagSubscriberTest extends TestCase
{
    #[Test]
    public function it_renders_the_init_and_the_track_call(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag)->add(self::event());

        $rendered = $tagBag->renderAll();

        self::assertStringContainsString("fbq('init', '1234'", $rendered);
        self::assertStringContainsString("fbq('track', 'ViewContent'", $rendered);
    }

    /**
     * The event id ties the browser event to the server event so Meta deduplicates the pair
     */
    #[Test]
    public function it_renders_the_event_id_for_deduplication(): void
    {
        $tagBag = self::tagBag();
        $event = self::event();

        self::subscriber($tagBag)->add($event);

        self::assertStringContainsString(sprintf("eventID: '%s'", $event->event->eventId), $tagBag->renderAll());
    }

    /**
     * The init tag has a higher priority than the one AddLibraryToTagBagSubscriber adds, so it replaces it
     */
    #[Test]
    public function its_init_tag_outranks_the_library_one(): void
    {
        $tagBag = self::tagBag();
        $tagBag->add(FbqInitTag::create('<script>the library init</script>', 50));

        self::subscriber($tagBag)->add(self::event());

        $rendered = $tagBag->renderAll();

        self::assertStringNotContainsString('the library init', $rendered);
        self::assertStringContainsString("fbq('init', '1234'", $rendered);
    }

    #[Test]
    public function it_renders_nothing_without_consent(): void
    {
        $tagBag = self::tagBag();

        self::subscriber($tagBag, consentGranted: false)->add(self::event());

        self::assertSame('', $tagBag->renderAll());
    }

    private static function subscriber(TagBag $tagBag, bool $consentGranted = true): AddEventToTagBagSubscriber
    {
        return new AddEventToTagBagSubscriber($tagBag, new FbqGenerator(), Doubles::consentChecker($consentGranted));
    }

    private static function event(): ConversionsApiEventRaised
    {
        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->pixels = [new Pixel('1234', 's3cr3t')];

        return new ConversionsApiEventRaised($metaEvent);
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
