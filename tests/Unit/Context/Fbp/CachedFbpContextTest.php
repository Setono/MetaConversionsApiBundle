<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Context\Fbp\CachedFbpContext;
use Setono\MetaConversionsApiBundle\Context\Fbp\FbpContextInterface;

#[CoversClass(CachedFbpContext::class)]
final class CachedFbpContextTest extends TestCase
{
    #[Test]
    public function it_calls_the_decorated_context_once(): void
    {
        $decorated = $this->createMock(FbpContextInterface::class);
        $decorated->expects(self::once())->method('getFbp')->willReturn(new Fbp());

        $context = new CachedFbpContext($decorated);

        self::assertSame($context->getFbp(), $context->getFbp());
    }

    #[Test]
    public function it_queries_the_decorated_context_again_after_reset(): void
    {
        $first = new Fbp();
        $second = new Fbp();

        $decorated = $this->createMock(FbpContextInterface::class);
        $decorated->expects(self::exactly(2))->method('getFbp')->willReturn($first, $second);

        $context = new CachedFbpContext($decorated);

        self::assertSame($first, $context->getFbp());

        $context->reset();

        // Without the reset the first visitor's fbp would be handed to every subsequent visitor
        self::assertSame($second, $context->getFbp());
    }
}
