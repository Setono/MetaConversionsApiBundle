<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbpContext implements FbpContextInterface
{
    public function __construct(
        private readonly FbpContextInterface $decorated,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFbp(): Fbp
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getFbp();
        }

        $fbp = $request->cookies->get(Cookies::FBP);
        if (is_string($fbp)) {
            try {
                return Fbp::fromString($fbp);
            } catch (\InvalidArgumentException) {
            }
        }

        return $this->decorated->getFbp();
    }
}
