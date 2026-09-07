<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class QueryBasedFbcContext implements FbcContextInterface
{
    public function __construct(
        private FbcContextInterface $decorated,
        private RequestStack $requestStack,
    ) {
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getFbc();
        }

        $facebookClickId = $request->query->get('fbclid');
        if (!is_string($facebookClickId) || '' === $facebookClickId) {
            return $this->decorated->getFbc();
        }

        return new Fbc($facebookClickId);
    }
}
