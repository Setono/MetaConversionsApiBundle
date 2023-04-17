<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\Provider\ConfigurationBasedPixelProvider;

/**
 * @covers \Setono\MetaConversionsApiBundle\Provider\ConfigurationBasedPixelProvider
 */
final class ConfigurationBasedPixelProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_properties(): void
    {
        $pixels = [
            ['id' => '', 'access_token' => ''],
            ['id' => '', 'access_token' => 's3cr3t'],
            ['id' => '1234'],
            ['id' => '1234', 'access_token' => ''],
            ['id' => '1234', 'access_token' => 's3cr3t'],
        ];
        $provider = new ConfigurationBasedPixelProvider($pixels);

        self::assertEquals([
            new Pixel('1234'),
            new Pixel('1234'),
            new Pixel('1234', 's3cr3t'),
        ], $provider->getPixels());
    }
}
