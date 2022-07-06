<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbcContext implements FbcContextInterface
{
    use MainRequestTrait;

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
        if (null === $request) {
            return null;
        }

        $fbc = $request->cookies->get('_fbc');
        if (is_string($fbc)) {
            try {
                return Fbc::fromString($fbc);
            } catch (\InvalidArgumentException $e) {
            }
        }

        return null;
    }
}
