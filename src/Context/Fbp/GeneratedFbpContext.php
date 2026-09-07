<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Cookie\CookieDomain;

final class GeneratedFbpContext implements FbpContextInterface
{
    public function __construct(private readonly CookieDomain $cookieDomain)
    {
    }

    public function getFbp(): Fbp
    {
        // The generated value is about to be written as a cookie, so it has to say which level it was set at
        return (new Fbp())->withSubdomainIndex($this->cookieDomain->subdomainIndex());
    }
}
