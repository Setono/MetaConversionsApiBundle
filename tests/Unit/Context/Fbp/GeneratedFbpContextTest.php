<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Unit\Context\Fbp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApiBundle\Context\Fbp\GeneratedFbpContext;
use Setono\MetaConversionsApiBundle\Cookie\CookieDomain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(GeneratedFbpContext::class)]
final class GeneratedFbpContextTest extends TestCase
{
    #[Test]
    public function it_generates_a_new_fbp_every_time(): void
    {
        $context = new GeneratedFbpContext(new CookieDomain(new RequestStack()));

        self::assertNotSame($context->getFbp()->value(), $context->getFbp()->value());
    }

    #[Test]
    public function it_records_the_level_the_cookie_will_be_written_at(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('https://www.example.com/'));

        // Host-only cookie on www.example.com, so the value has to say fb.2.
        self::assertSame(2, (new GeneratedFbpContext(new CookieDomain($requestStack)))->getFbp()->getSubdomainIndex());

        // ... but a cookie written on example.com is fb.1.
        self::assertSame(1, (new GeneratedFbpContext(new CookieDomain($requestStack, 'example.com')))->getFbp()->getSubdomainIndex());
    }
}
