<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Context\Fbc\CookieBasedFbcContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(CookieBasedFbcContext::class)]
final class CookieBasedFbcContextTest extends TestCase
{
    #[Test]
    public function it_returns_null_without_a_request(): void
    {
        self::assertNull((new CookieBasedFbcContext(new RequestStack()))->getFbc());
    }

    #[Test]
    public function it_returns_null_without_a_cookie(): void
    {
        self::assertNull((new CookieBasedFbcContext(self::requestStack(null)))->getFbc());
    }

    #[Test]
    #[DataProvider('cookies')]
    public function it_parses_the_cookie(string $cookie, string $expectedClickId, int $expectedSubdomainIndex, int $expectedCreationTime): void
    {
        $fbc = (new CookieBasedFbcContext(self::requestStack($cookie)))->getFbc();

        self::assertInstanceOf(Fbc::class, $fbc);
        self::assertSame($expectedClickId, $fbc->getClickId());
        self::assertSame($expectedSubdomainIndex, $fbc->getSubdomainIndex());
        self::assertSame($expectedCreationTime, $fbc->getCreationTime());
    }

    /**
     * @return iterable<string, array{string, string, int, int}>
     */
    public static function cookies(): iterable
    {
        yield 'alphanumeric click id' => [
            'fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA',
            'IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA',
            1,
            1657051589577,
        ];

        // Real click ids are base64url, so they contain - and _
        yield 'base64url click id' => [
            'fb.1.1657051589577.IwZXh0bgNhZW0CMTAAAR-uK_5w',
            'IwZXh0bgNhZW0CMTAAAR-uK_5w',
            1,
            1657051589577,
        ];

        // Meta's own parameter builder appends a 2 or 8 character appendix
        yield 'appendix v1' => [
            'fb.2.1657051589577.IwAR0rmfgHgx.AQ',
            'IwAR0rmfgHgx',
            2,
            1657051589577,
        ];

        yield 'appendix v2' => [
            'fb.0.1657051589577.IwAR0rmfgHgx.AbCdEfGh',
            'IwAR0rmfgHgx',
            0,
            1657051589577,
        ];
    }

    #[Test]
    #[DataProvider('invalidCookies')]
    public function it_returns_null_for_an_unparseable_cookie(string $cookie): void
    {
        self::assertNull((new CookieBasedFbcContext(self::requestStack($cookie)))->getFbc());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCookies(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['not-an-fbc-cookie'];
        yield 'wrong prefix' => ['xx.1.1657051589577.abc'];
        yield 'invalid subdomain index' => ['fb.3.1657051589577.abc'];
        yield 'short creation time' => ['fb.1.165705158.abc'];
        yield 'missing click id' => ['fb.1.1657051589577.'];
        yield 'creation time in the future' => ['fb.1.9999999999999.abc'];
    }

    private static function requestStack(?string $cookie): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [], null === $cookie ? [] : ['_fbc' => $cookie]));

        return $requestStack;
    }
}
