<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Component\HttpFoundation\RequestStack;

final class QueryBasedFbcContext implements FbcContextInterface
{
    use MainRequestTrait;

    private FbcContextInterface $decorated;

    private RequestStack $requestStack;

    public function __construct(FbcContextInterface $decorated, RequestStack $requestStack)
    {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
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
