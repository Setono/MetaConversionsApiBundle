<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests;

use Nyholm\BundleTest\TestKernel;
use Psr\Container\ContainerInterface;
use Setono\BotDetectionBundle\SetonoBotDetectionBundle;
use Setono\ConsentBundle\SetonoConsentBundle;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddEventToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\AddLibraryToTagBagSubscriber;
use Setono\MetaConversionsApiBundle\EventSubscriber\DispatchOnCommandBusSubscriber;
use Setono\MetaConversionsApiBundle\Message\Handler\SendEventHandler;
use Setono\MetaConversionsApiBundle\SetonoMetaConversionsApiBundle;
use Setono\TagBagBundle\SetonoTagBagBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(SetonoMetaConversionsApiBundle::class);
        $kernel->addTestBundle(SetonoBotDetectionBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    /**
     * @test
     */
    public function it_throws_exception_if_client_side_is_enabled_but_tag_bag_is_not_enabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::bootKernel();
    }

    /**
     * @test
     */
    public function it_boots_with_client_side(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
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

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        foreach ($this->clientSideServices as $service) {
            self::assertTrue($container->has($service));
        }
    }

    /**
     * @test
     */
    public function it_boots_without_client_side(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                ]);
            });
        }]);

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        foreach ($this->clientSideServices as $service) {
            self::assertFalse($container->has($service));
        }
    }

    /**
     * @test
     */
    public function it_boots_with_server_side(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => true,
                ]);
            });
        }]);

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        foreach ($this->serverSideServices as $service) {
            self::assertTrue($container->has($service));
        }
    }

    /**
     * @test
     */
    public function it_boots_without_server_side(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });
        }]);

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        foreach ($this->serverSideServices as $service) {
            self::assertFalse($container->has($service));
        }
    }

    /**
     * @test
     */
    public function it_works_with_consent_bundle(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'consent' => true,
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });

            $kernel->addTestBundle(SetonoConsentBundle::class);
        }]);

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        self::assertTrue($container->has(ConsentCheckerInterface::class));

        $consentChecker = $container->get(ConsentCheckerInterface::class);
        self::assertFalse($consentChecker->isGranted());
    }

    /**
     * @test
     */
    public function it_works_without_consent_bundle(): void
    {
        $kernel = self::bootKernel(['config' => function (TestKernel $kernel) {
            $kernel->addTestConfig(static function (ContainerBuilder $container) {
                $container->loadFromExtension('setono_meta_conversions_api', [
                    'client_side' => false,
                    'server_side' => false,
                ]);
            });
        }]);

        /** @var ContainerInterface $container */
        $container = $kernel->getContainer()->get('test.service_container');

        self::assertTrue($container->has(ConsentCheckerInterface::class));

        $consentChecker = $container->get(ConsentCheckerInterface::class);
        self::assertTrue($consentChecker->isGranted());
    }
}
