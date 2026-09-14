<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Integration;

use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\Test;
use Setono\BotDetectionBundle\SetonoBotDetectionBundle;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApiBundle\Event\ConversionsApiEventRaised;
use Setono\MetaConversionsApiBundle\SetonoMetaConversionsApiBundle;
use Setono\MetaConversionsApiBundle\Tests\Double\RecordingConversionsApiClientFactory;
use Setono\TagBag\TagBagInterface;
use Setono\TagBagBundle\SetonoTagBagBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Dispatches a real event through a booted kernel and asserts what comes out the other end
 *
 * The unit tests cover each listener on its own. This one covers the wiring between them, which is where a
 * regression like a missing service argument or a bus that no longer exists actually shows up
 */
final class PipelineTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(SetonoMetaConversionsApiBundle::class);
        $kernel->addTestBundle(SetonoBotDetectionBundle::class);
        $kernel->addTestBundle(SetonoTagBagBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    protected function setUp(): void
    {
        RecordingConversionsApiClientFactory::reset();
    }

    #[Test]
    public function it_sends_an_enriched_event_and_renders_the_tags(): void
    {
        self::boot();
        self::pushRequest('Mozilla/5.0 (Macintosh) Chrome/140.0');

        $container = self::getContainer();

        $dispatcher = $container->get('test.event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $metaEvent = new Event(Event::EVENT_VIEW_CONTENT);
        $metaEvent->customData->contentName = 'Blue Jeans';

        // An application listener enriching at the documented priority
        $dispatcher->addListener(
            ConversionsApiEventRaised::class,
            static function (ConversionsApiEventRaised $event): void {
                $event->event->userData->email[] = 'customer@example.com';
            },
            ConversionsApiEventRaised::PRIORITY_ENRICH,
        );

        $dispatcher->dispatch(new ConversionsApiEventRaised($metaEvent), ConversionsApiEventRaised::class);

        // Server side: the command was dispatched, handled, and reached the client
        self::assertCount(1, RecordingConversionsApiClientFactory::$events);
        $sent = RecordingConversionsApiClientFactory::$events[0]->getPayload();

        self::assertSame('ViewContent', $sent['event_name']);
        self::assertSame('https://example.com/jeans', $sent['event_source_url']);
        self::assertSame($metaEvent->eventId, $sent['event_id']);

        $userData = $sent['user_data'];
        self::assertIsArray($userData);
        self::assertSame('Mozilla/5.0 (Macintosh) Chrome/140.0', $userData['client_user_agent']);
        self::assertArrayHasKey('fbp', $userData);
        // The application's email is hashed, never sent raw
        self::assertSame([hash('sha256', 'customer@example.com')], $userData['em']);

        // Client side: the pixel, the init and the track call are in the tag bag
        $tagBag = $container->get('test.tag_bag');
        self::assertInstanceOf(TagBagInterface::class, $tagBag);

        // The library tag itself hangs off kernel.request, which this test does not fire, so it is covered by
        // AddLibraryToTagBagSubscriberTest instead
        $rendered = $tagBag->renderAll();
        self::assertStringContainsString("fbq('init', '1234'", $rendered);
        self::assertStringContainsString("fbq('track', 'ViewContent'", $rendered);
        // The same event id on both sides is what makes Meta deduplicate the pair
        self::assertStringContainsString(sprintf("eventID: '%s'", $metaEvent->eventId), $rendered);
    }

    #[Test]
    public function it_sends_nothing_for_a_bot(): void
    {
        self::boot();
        self::pushRequest('Googlebot/2.1 (+http://www.google.com/bot.html)');

        $container = self::getContainer();

        $dispatcher = $container->get('test.event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $enriched = false;
        $dispatcher->addListener(
            ConversionsApiEventRaised::class,
            static function () use (&$enriched): void {
                $enriched = true;
            },
            ConversionsApiEventRaised::PRIORITY_ENRICH,
        );

        $dispatcher->dispatch(new ConversionsApiEventRaised(new Event(Event::EVENT_VIEW_CONTENT)), ConversionsApiEventRaised::class);

        self::assertSame([], RecordingConversionsApiClientFactory::$events);
        // ... and the application never spent anything enriching it
        self::assertFalse($enriched);

        $tagBag = $container->get('test.tag_bag');
        self::assertInstanceOf(TagBagInterface::class, $tagBag);
        self::assertStringNotContainsString('fbq(', $tagBag->renderAll());
    }

    private static function boot(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_tag_bag', [
                    'renderer' => ['twig' => false],
                ]);
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => true,
                    'pixels' => [
                        ['id' => '1234', 'access_token' => 's3cr3t'],
                    ],
                ]);

                $container->register(ClientInterface::class, ClientInterface::class)
                    ->setFactory([RecordingConversionsApiClientFactory::class, 'create']);

                $container->setAlias('test.event_dispatcher', 'event_dispatcher')->setPublic(true);
                $container->setAlias('test.tag_bag', 'setono_tag_bag.tag_bag')->setPublic(true);
                $container->setAlias('test.request_stack', 'request_stack')->setPublic(true);
            });
        }]);
    }

    private static function pushRequest(string $userAgent): void
    {
        $request = Request::create('https://example.com/jeans');
        $request->headers->set('User-Agent', $userAgent);

        $requestStack = self::getContainer()->get('test.request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push($request);
    }
}
