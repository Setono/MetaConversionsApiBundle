<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;

final class CachedFbpContext implements FbpContextInterface
{
    private FbpContextInterface $decorated;

    private ?Fbp $cached = null;

    public function __construct(FbpContextInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getFbp(): Fbp
    {
        if (null === $this->cached) {
            $this->cached = $this->decorated->getFbp();
        }

        return $this->cached;
    }
}
