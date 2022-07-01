<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\MetaConversionsApiBundle\DependencyInjection\SetonoMetaConversionsApiExtension;

/**
 * @covers \Setono\MetaConversionsApiBundle\DependencyInjection\SetonoMetaConversionsApiExtension
 */
final class SetonoTwigCachePurgerExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoMetaConversionsApiExtension(),
        ];
    }

    /**
     * @test
     */
    public function it_registers_services(): void
    {
        $this->load();
    }
}
