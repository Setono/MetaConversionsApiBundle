<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\ConsentChecker;

interface ConsentCheckerInterface
{
    /**
     * Returns true if consent is given to track the user in Facebook
     */
    public function isGranted(): bool;
}
