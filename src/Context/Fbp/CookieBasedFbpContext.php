<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MetaConversionsApi\ValueObject\Fbp;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CookieBasedFbpContext implements FbpContextInterface
{
    public function __construct(
        private FbpContextInterface $decorated,
        private RequestStack $requestStack,
    ) {
    }

    public function getFbp(): Fbp
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getFbp();
        }

        $fbp = $request->cookies->get('_fbp');
        if (is_string($fbp)) {
            try {
                return Fbp::fromString($fbp);
            } catch (\InvalidArgumentException) {
            }
        }

        return $this->decorated->getFbp();
    }
}
