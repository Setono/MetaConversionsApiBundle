<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Integration;

use FacebookAds\ApiConfig;
use Nyholm\BundleTest\TestKernel;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\Test;
use Setono\BotDetectionBundle\SetonoBotDetectionBundle;
use Setono\ConsentBundle\SetonoConsentBundle;
use Setono\MetaConversionsApi\Client\ClientInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddLibraryToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;
use Setono\MetaConversionsApiBundle\SetonoMetaConversionsApiBundle;
use Setono\MetaConversionsApiBundle\Tests\Double\RecordingHttpClientFactory;
use Setono\TagBagBundle\SetonoTagBagBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpKernel\KernelInterface;

final class SetonoMetaConversionsApiBundleTest extends KernelTestCase
{
    /** @var list<class-string> */
    private array $clientSideServices;

    /** @var list<class-string> */
    private array $serverSideServices;

    protected function setUp(): void
    {
        $this->clientSideServices = [
            AddEventToTagBagSubscriber::class,
            AddLibraryToTagBagSubscriber::class,
        ];

        $this->serverSideServices = [
            DispatchOnCommandBusSubscriber::class,
            SendEventHandler::class,
        ];
    }

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
        $kernel->handleOptions($options);

        return $kernel;
    }

    #[Test]
    public function it_throws_exception_if_client_side_is_enabled_but_tag_bag_is_not_enabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::bootKernel();
    }

    #[Test]
    public function it_boots_with_client_side(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestBundle(SetonoTagBagBundle::class);
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_tag_bag', [
                    'renderer' => [
                        'twig' => false,
                    ],
                ]);
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => true,
                ]);
            });
        }]);

        $container = self::getContainer();

        foreach ($this->clientSideServices as $service) {
            self::assertTrue($container->has($service));
        }
    }

    #[Test]
    public function it_boots_without_client_side(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                ]);
            });
        }]);

        $container = self::getContainer();

        foreach ($this->clientSideServices as $service) {
            self::assertFalse($container->has($service));
        }
    }

    #[Test]
    public function it_boots_with_server_side(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => true,
                ]);
            });
        }]);

        $container = self::getContainer();

        foreach ($this->serverSideServices as $service) {
            self::assertTrue($container->has($service));
        }
    }

    #[Test]
    public function it_boots_without_server_side(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });
        }]);

        $container = self::getContainer();

        foreach ($this->serverSideServices as $service) {
            self::assertFalse($container->has($service));
        }
    }

    #[Test]
    public function it_keeps_the_frameworks_default_message_bus(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                ]);
            });
        }]);

        $container = self::getContainer();

        // The bundle must not remove the bus the application (and other bundles) rely on
        self::assertTrue($container->has('messenger.bus.default'));
    }

    #[Test]
    public function it_boots_when_the_application_defines_its_own_bus_without_a_default_bus(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('framework', [
                    'messenger' => [
                        'buses' => [
                            'command.bus' => null,
                        ],
                    ],
                ]);
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                ]);
            });
        }]);

        self::assertTrue(self::getContainer()->has(DispatchOnCommandBusSubscriber::class));
    }

    #[Test]
    public function it_dispatches_on_the_configured_message_bus(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('framework', [
                    'messenger' => [
                        'default_bus' => 'command.bus',
                        'buses' => [
                            'command.bus' => null,
                            'event.bus' => null,
                        ],
                    ],
                ]);
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => [
                        'message_bus' => 'event.bus',
                    ],
                ]);
                $container->setAlias('setono_meta_conversions_api.message_bus.test', 'setono_meta_conversions_api.message_bus')
                    ->setPublic(true);
                $container->setAlias('event.bus.test', 'event.bus')->setPublic(true);
            });
        }]);

        $container = self::getContainer();

        self::assertSame(
            $container->get('event.bus.test'),
            $container->get('setono_meta_conversions_api.message_bus.test'),
        );
    }

    #[Test]
    public function it_sends_events_through_the_applications_http_client(): void
    {
        RecordingHttpClientFactory::reset();

        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                ]);

                $container->register('test.psr17_factory', Psr17Factory::class);
                $container->register('test.mock_http_client', MockHttpClient::class)
                    ->setFactory([RecordingHttpClientFactory::class, 'create']);

                // Replacing the application's PSR-18 client must be enough to intercept everything the bundle
                // sends. That only holds because the client is wired instead of discovered at runtime
                $container->register('psr18.http_client', Psr18Client::class)
                    ->setArguments([
                        new Reference('test.mock_http_client'),
                        new Reference('test.psr17_factory'),
                        new Reference('test.psr17_factory'),
                    ]);

                $container->setAlias('test.conversions_api_client', ClientInterface::class)->setPublic(true);
            });
        }]);

        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->pixels = [new Pixel('1234', 's3cr3t')];

        $client = self::getContainer()->get('test.conversions_api_client');
        self::assertInstanceOf(ClientInterface::class, $client);
        $client->sendEvent($event);

        // The Graph API version follows whichever facebook/php-business-sdk is installed
        self::assertSame(
            [['POST', sprintf('https://graph.facebook.com/v%s/1234/events', ApiConfig::APIVersion)]],
            RecordingHttpClientFactory::$requests,
        );
    }

    #[Test]
    public function it_boots_when_the_configured_http_client_does_not_exist(): void
    {
        // Without symfony/http-client there is no psr18.http_client, and the SDK falls back to discovery
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'http_client' => 'a.http.client.that.does.not.exist',
                ]);
            });
        }]);

        self::assertTrue(self::getContainer()->has(SendEventHandler::class));
    }

    #[Test]
    public function it_works_with_consent_bundle(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'consent' => true,
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });

            $kernel->addTestBundle(SetonoConsentBundle::class);
        }]);

        $container = self::getContainer();

        self::assertTrue($container->has(ConsentCheckerInterface::class));

        $consentChecker = $container->get(ConsentCheckerInterface::class);
        self::assertInstanceOf(ConsentCheckerInterface::class, $consentChecker);
        self::assertFalse($consentChecker->isGranted());
    }

    #[Test]
    public function it_works_without_consent_bundle(): void
    {
        self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });
        }]);

        $container = self::getContainer();

        self::assertTrue($container->has(ConsentCheckerInterface::class));

        $consentChecker = $container->get(ConsentCheckerInterface::class);
        self::assertInstanceOf(ConsentCheckerInterface::class, $consentChecker);
        self::assertTrue($consentChecker->isGranted());
    }
}
