<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Caches the fbp for the duration of the request.
 *
 * The cache is reset between requests (see the kernel.reset tag on this service), which matters in long running
 * runtimes like FrankenPHP worker mode, RoadRunner and Swoole where the same container serves many visitors.
 * Without the reset every visitor without a _fbp cookie would share the fbp generated for the first one.
 */
final class CachedFbpContext implements FbpContextInterface, ResetInterface
{
    private ?Fbp $cached = null;

    public function __construct(private readonly FbpContextInterface $decorated)
    {
    }

    public function getFbp(): Fbp
    {
        $this->cached ??= $this->decorated->getFbp();

        return $this->cached;
    }

    public function reset(): void
    {
        $this->cached = null;
    }
}
