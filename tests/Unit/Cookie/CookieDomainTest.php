<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Cookie;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApiBundle\Cookie\CookieDomain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(CookieDomain::class)]
final class CookieDomainTest extends TestCase
{
    #[Test]
    public function it_returns_no_domain_by_default(): void
    {
        self::assertNull((new CookieDomain(new RequestStack()))->domain());
    }

    #[Test]
    public function it_returns_the_configured_domain(): void
    {
        self::assertSame('example.com', (new CookieDomain(new RequestStack(), 'example.com'))->domain());
    }

    /**
     * Meta's own parameter builder computes this as the number of dots in the domain the cookie ends up on
     */
    #[Test]
    #[DataProvider('configuredDomains')]
    public function it_derives_the_subdomain_index_from_the_configured_domain(string $domain, int $expected): void
    {
        self::assertSame($expected, (new CookieDomain(new RequestStack(), $domain))->subdomainIndex());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function configuredDomains(): iterable
    {
        yield 'single label' => ['localhost', 0];
        yield 'registrable domain' => ['example.com', 1];
        yield 'leading dot' => ['.example.com', 1];
        yield 'subdomain' => ['www.example.com', 2];
        yield 'deep subdomain is capped' => ['shop.eu.example.com', 2];
    }

    #[Test]
    #[DataProvider('hosts')]
    public function it_falls_back_to_the_request_host(string $host, int $expected): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://' . $host . '/'));

        self::assertSame($expected, (new CookieDomain($requestStack))->subdomainIndex());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function hosts(): iterable
    {
        yield 'single label' => ['localhost', 0];
        yield 'registrable domain' => ['example.com', 1];
        yield 'subdomain' => ['www.example.com', 2];
    }

    #[Test]
    public function it_returns_zero_without_a_request_or_a_domain(): void
    {
        self::assertSame(0, (new CookieDomain(new RequestStack()))->subdomainIndex());
    }
}
