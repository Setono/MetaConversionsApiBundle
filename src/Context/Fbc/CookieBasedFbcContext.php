<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbcContext implements FbcContextInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        $fbc = $request->cookies->get(Cookies::FBC);
        if (is_string($fbc)) {
            try {
                return Fbc::fromString($fbc);
            } catch (\InvalidArgumentException) {
            }
        }

        return null;
    }
}
