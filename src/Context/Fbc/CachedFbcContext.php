<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Caches the fbc for the duration of the request.
 *
 * The cache is reset between requests (see the kernel.reset tag on this service), which matters in long running
 * runtimes like FrankenPHP worker mode, RoadRunner and Swoole where the same container serves many visitors.
 */
final class CachedFbcContext implements FbcContextInterface, ResetInterface
{
    private bool $cached = false;

    private ?Fbc $value = null;

    public function __construct(private readonly FbcContextInterface $decorated)
    {
    }

    public function getFbc(): ?Fbc
    {
        if (!$this->cached) {
            $this->value = $this->decorated->getFbc();
            $this->cached = true;
        }

        return $this->value;
    }

    public function reset(): void
    {
        $this->cached = false;
        $this->value = null;
    }
}
