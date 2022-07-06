<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;

final class GeneratedFbpContext implements FbpContextInterface
{
    public function getFbp(): Fbp
    {
        return new Fbp();
    }
}
