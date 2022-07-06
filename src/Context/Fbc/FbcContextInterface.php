<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;

interface FbcContextInterface
{
    public function getFbc(): ?Fbc;
}
