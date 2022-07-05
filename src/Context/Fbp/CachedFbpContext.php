<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

final class CachedFbpContext implements FbpContextInterface
{
    private FbpContextInterface $decorated;

    private ?string $cached = null;

    public function __construct(FbpContextInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getFbp(): string
    {
        if (null === $this->cached) {
            $this->cached = $this->decorated->getFbp();
        }

        return $this->cached;
    }
}
