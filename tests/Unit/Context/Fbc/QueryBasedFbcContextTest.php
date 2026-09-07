<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;
use Setono\MetaConversionsApiBundle\Context\Fbc\QueryBasedFbcContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(QueryBasedFbcContext::class)]
final class QueryBasedFbcContextTest extends TestCase
{
    #[Test]
    public function it_falls_back_to_the_decorated_context_without_a_request(): void
    {
        $fbc = new Fbc('decorated');

        $context = new QueryBasedFbcContext(self::decorated($fbc), new RequestStack());

        self::assertSame($fbc, $context->getFbc());
    }

    #[Test]
    #[DataProvider('validClickIds')]
    public function it_uses_a_valid_click_id_from_the_query(string $clickId): void
    {
        $context = new QueryBasedFbcContext(self::decorated(null), self::requestStack($clickId));

        $fbc = $context->getFbc();

        self::assertInstanceOf(Fbc::class, $fbc);
        self::assertSame($clickId, $fbc->getClickId());
    }

    /**
     * @return iterable<array-key, array{string}>
     */
    public static function validClickIds(): iterable
    {
        yield ['IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA'];
        // Real click ids are base64url, i.e. they contain - and _
        yield ['IwZXh0bgNhZW0CMTAAAR-uK_5w'];
        yield ['a'];
    }

    #[Test]
    #[DataProvider('invalidClickIds')]
    public function it_falls_back_to_the_decorated_context_for_an_invalid_click_id(string $clickId): void
    {
        $decorated = new Fbc('decorated');

        $context = new QueryBasedFbcContext(self::decorated($decorated), self::requestStack($clickId));

        self::assertSame($decorated, $context->getFbc());
    }

    /**
     * @return iterable<array-key, array{string}>
     */
    public static function invalidClickIds(): iterable
    {
        yield 'empty' => [''];
        yield 'too long' => [str_repeat('a', 256)];
        yield 'html' => ['<script>alert(1)</script>'];
        yield 'whitespace' => ['abc def'];
        yield 'dot' => ['fb.1.123.abc'];
    }

    private static function decorated(?Fbc $fbc): FbcContextInterface
    {
        return new class($fbc) implements FbcContextInterface {
            public function __construct(private readonly ?Fbc $fbc)
            {
            }

            public function getFbc(): ?Fbc
            {
                return $this->fbc;
            }
        };
    }

    private static function requestStack(string $clickId): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['fbclid' => $clickId]));

        return $requestStack;
    }
}
