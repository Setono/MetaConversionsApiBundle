<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\AccessTokenResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApiBundle\AccessTokenResolver\ConfigurationBasedAccessTokenResolver;

#[CoversClass(ConfigurationBasedAccessTokenResolver::class)]
final class ConfigurationBasedAccessTokenResolverTest extends TestCase
{
    #[Test]
    public function it_resolves_a_configured_access_token(): void
    {
        $resolver = new ConfigurationBasedAccessTokenResolver([
            ['id' => '1234', 'access_token' => 's3cr3t'],
        ]);

        // Pixel ids are numeric strings, which PHP turns into integer array keys. The lookup must survive that
        self::assertSame('s3cr3t', $resolver->resolve('1234'));
    }

    #[Test]
    public function it_returns_null_for_an_unknown_pixel(): void
    {
        $resolver = new ConfigurationBasedAccessTokenResolver([
            ['id' => '1234', 'access_token' => 's3cr3t'],
        ]);

        self::assertNull($resolver->resolve('4321'));
    }

    #[Test]
    public function it_returns_null_for_a_pixel_without_an_access_token(): void
    {
        $resolver = new ConfigurationBasedAccessTokenResolver([
            ['id' => '1234'],
            ['id' => '4321', 'access_token' => ''],
            ['id' => '9999', 'access_token' => null],
        ]);

        self::assertNull($resolver->resolve('1234'));
        self::assertNull($resolver->resolve('4321'));
        self::assertNull($resolver->resolve('9999'));
    }
}
