<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context;

use Setono\MetaConversionsApi\Generator\FbpGeneratorInterface;

final class GeneratedFbpContext implements FbpContextInterface
{
    private FbpGeneratorInterface $fbpGenerator;

    public function __construct(FbpGeneratorInterface $fbpGenerator)
    {
        $this->fbpGenerator = $fbpGenerator;
    }

    public function getFbp(): string
    {
        return $this->fbpGenerator->generate();
    }
}
