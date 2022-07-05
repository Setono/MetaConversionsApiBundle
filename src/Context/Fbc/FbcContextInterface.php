<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

interface FbcContextInterface
{
    public function getFbc(): ?string;
}
