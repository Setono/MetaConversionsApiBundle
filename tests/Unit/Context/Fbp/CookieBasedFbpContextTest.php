<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Context\Fbp\CookieBasedFbpContext;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(CookieBasedFbpContext::class)]
final class CookieBasedFbpContextTest extends TestCase
{
    #[Test]
    public function it_falls_back_to_the_decorated_context_without_a_request(): void
    {
        $generated = new Fbp();

        self::assertSame($generated, (new CookieBasedFbpContext(self::decorated($generated), new RequestStack()))->getFbp());
    }

    #[Test]
    public function it_falls_back_to_the_decorated_context_without_a_cookie(): void
    {
        $generated = new Fbp();

        self::assertSame($generated, (new CookieBasedFbpContext(self::decorated($generated), self::requestStack(null)))->getFbp());
    }

    /**
     * The important one. A cookie the bundle cannot read makes it generate a fresh fbp on every request while the
     * browser holds a stable one, so the two sides stop describing the same person
     */
    #[Test]
    #[DataProvider('cookies')]
    public function it_reuses_the_value_the_browser_already_has(string $cookie): void
    {
        $context = new CookieBasedFbpContext(self::decorated(new Fbp()), self::requestStack($cookie));

        self::assertSame($cookie, $context->getFbp()->value());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cookies(): iterable
    {
        yield 'four segments' => ['fb.1.1657051589577.1088522659'];

        // Meta's own parameter builder, and the browser pixel, append a 2 or 8 character appendix
        yield 'appendix v1' => ['fb.2.1657051589577.1088522659.AQ'];
        yield 'appendix v2' => ['fb.0.1657051589577.1088522659.AQEAAQMB'];

        // The index is the number of dots in the domain the cookie is set on, so a.b.example.co.uk gives 3
        yield 'a cookie set on a deeper domain' => ['fb.3.1657051589577.1088522659'];
    }

    #[Test]
    #[DataProvider('invalidCookies')]
    public function it_falls_back_for_an_unparseable_cookie(string $cookie): void
    {
        $generated = new Fbp();

        self::assertSame($generated, (new CookieBasedFbpContext(self::decorated($generated), self::requestStack($cookie)))->getFbp());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCookies(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['not-an-fbp-cookie'];
        yield 'wrong prefix' => ['xx.1.1657051589577.1088522659'];
        yield 'subdomain index with a leading zero' => ['fb.03.1657051589577.1088522659'];
        yield 'short creation time' => ['fb.1.165705158.1088522659'];
        yield 'creation time in the future' => ['fb.1.9999999999999.1088522659'];
        yield 'non numeric random part' => ['fb.1.1657051589577.abc'];
    }

    #[Test]
    public function it_says_when_it_had_to_generate_a_new_fbp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')->with(self::stringContains('no longer match'));

        (new CookieBasedFbpContext(self::decorated(new Fbp()), self::requestStack('not-an-fbp-cookie'), $logger))->getFbp();
    }

    #[Test]
    public function it_does_not_log_for_a_visitor_without_a_cookie(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method(self::anything());

        (new CookieBasedFbpContext(self::decorated(new Fbp()), self::requestStack(null), $logger))->getFbp();
    }

    private static function decorated(Fbp $fbp): FbpContextInterface
    {
        return new class($fbp) implements FbpContextInterface {
            public function __construct(private readonly Fbp $fbp)
            {
            }

            public function getFbp(): Fbp
            {
                return $this->fbp;
            }
        };
    }

    private static function requestStack(?string $cookie): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [], null === $cookie ? [] : ['_fbp' => $cookie]));

        return $requestStack;
    }
}
