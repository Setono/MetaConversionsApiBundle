<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;

interface FbpContextInterface
{
    public function getFbp(): Fbp;
}
