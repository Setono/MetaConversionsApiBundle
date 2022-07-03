<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context;

use Setono\MainRequestTrait\MainRequestTrait;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbcContext implements FbcContextInterface
{
    use MainRequestTrait;

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFbc(): ?string
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
        if (null === $request) {
            return null;
        }

        $fbc = $request->cookies->get('_fbc');

        return is_string($fbc) ? $fbc : null;
    }
}
