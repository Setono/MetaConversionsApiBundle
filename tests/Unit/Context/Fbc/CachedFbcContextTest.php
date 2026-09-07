<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Context\Fbc\CachedFbcContext;
use Setono\MetaConversionsApiBundle\Context\Fbc\FbcContextInterface;

#[CoversClass(CachedFbcContext::class)]
final class CachedFbcContextTest extends TestCase
{
    #[Test]
    public function it_calls_the_decorated_context_once(): void
    {
        $decorated = $this->createMock(FbcContextInterface::class);
        $decorated->expects(self::once())->method('getFbc')->willReturn(new Fbc('click-id'));

        $context = new CachedFbcContext($decorated);

        self::assertSame($context->getFbc(), $context->getFbc());
    }

    #[Test]
    public function it_caches_null(): void
    {
        $decorated = $this->createMock(FbcContextInterface::class);
        $decorated->expects(self::once())->method('getFbc')->willReturn(null);

        $context = new CachedFbcContext($decorated);

        self::assertNull($context->getFbc());
        self::assertNull($context->getFbc());
    }

    #[Test]
    public function it_queries_the_decorated_context_again_after_reset(): void
    {
        $first = new Fbc('first');
        $second = new Fbc('second');

        $decorated = $this->createMock(FbcContextInterface::class);
        $decorated->expects(self::exactly(2))->method('getFbc')->willReturn($first, $second);

        $context = new CachedFbcContext($decorated);

        self::assertSame($first, $context->getFbc());

        $context->reset();

        self::assertSame($second, $context->getFbc());
    }
}
