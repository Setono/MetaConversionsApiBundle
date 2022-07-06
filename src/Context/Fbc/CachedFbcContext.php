<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;

final class CachedFbcContext implements FbcContextInterface
{
    private FbcContextInterface $decorated;

    private bool $cached = false;

    private ?Fbc $value = null;

    public function __construct(FbcContextInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getFbc(): ?Fbc
    {
        if (!$this->cached) {
            $this->value = $this->decorated->getFbc();
            $this->cached = true;
        }

        return $this->value;
    }
}
