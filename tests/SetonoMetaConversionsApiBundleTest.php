<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests;

use Nyholm\BundleTest\TestKernel;
use Setono\BotDetectionBundle\SetonoBotDetectionBundle;
use Setono\MetaConversionsApi\Client\Client;
use Setono\MetaConversionsApiBundle\Context\Fbc\CachedFbcContext;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Context\Fbp\CachedFbpContext;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\FilterBotsSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateFbpAndFbcPropertiesSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulatePixelsSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateRequestPropertiesSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\PopulateTestEventCodePropertySubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreFbcSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreFbpSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\StoreTestEventCodeSubscriber;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;
use Setono\MetaConversionsApiBundle\Provider\ConfigurationBasedPixelProvider;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;
use Setono\MetaConversionsApiBundle\SetonoMetaConversionsApiBundle;
use Setono\TagBagBundle\SetonoTagBagBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBus;

final class SetonoMetaConversionsApiBundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var TestKernel $kernel
         */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(SetonoMetaConversionsApiBundle::class);
        $kernel->addTestBundle(SetonoBotDetectionBundle::class);
        $kernel->addTestBundle(SetonoTagBagBundle::class);
        $kernel->addTestBundle(TwigBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    /**
     * @test
     */
    public function it_initializes(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var list<array{id: string, class: class-string}> $services */
        $services = [
            ['id' => 'setono_meta_conversions_api.command_bus', 'class' => MessageBus::class],

            // client.xml
            ['id' => 'setono_meta_conversions_api.client.default', 'class' => Client::class],

            // context.xml
            ['id' => FbcContextInterface::class, 'class' => CachedFbcContext::class],
            ['id' => 'setono_meta_conversions_api.context.fbc', 'class' => CachedFbcContext::class],

            ['id' => FbpContextInterface::class, 'class' => CachedFbpContext::class],
            ['id' => 'setono_meta_conversions_api.context.fbp', 'class' => CachedFbpContext::class],

            // event_subscriber.xml
            ['id' => 'setono_meta_conversions_api.event_subscriber.dispatch_on_command_bus', 'class' => DispatchOnCommandBusSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.filter_bots', 'class' => FilterBotsSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.populate_fbp_and_fbc_properties', 'class' => PopulateFbpAndFbcPropertiesSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.populate_pixels', 'class' => PopulatePixelsSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.populate_request_properties', 'class' => PopulateRequestPropertiesSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.populate_test_event_code_property', 'class' => PopulateTestEventCodePropertySubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.store_fbc', 'class' => StoreFbcSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.store_fbp', 'class' => StoreFbpSubscriber::class],
            ['id' => 'setono_meta_conversions_api.event_subscriber.store_test_event_code', 'class' => StoreTestEventCodeSubscriber::class],

            // generator.xml
            //['id' => 'setono_meta_conversions_api.generator.fbq', 'class' => FbqGenerator::class],

            // message.xml
            ['id' => 'setono_meta_conversions_api.message_handler.send_event', 'class' => SendEventHandler::class],

            // provider.xml
            ['id' => PixelProviderInterface::class, 'class' => ConfigurationBasedPixelProvider::class],
            ['id' => 'setono_meta_conversions_api.pixel_provider.default', 'class' => ConfigurationBasedPixelProvider::class],
            ['id' => 'setono_meta_conversions_api.pixel_provider.configuration_based', 'class' => ConfigurationBasedPixelProvider::class],
        ];

        foreach ($services as $service) {
            self::assertTrue($container->has($service['id']), sprintf('Service %s does not exist', $service['id']));

            /** @var object $obj */
            $obj = $container->get($service['id']);

            self::assertInstanceOf($service['class'], $obj, sprintf(
                'Service %s was not an instance of %s, but an instance of %s',
                $service['id'],
                $service['class'],
                get_class($obj)
            ));
        }
    }
}
