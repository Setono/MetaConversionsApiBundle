<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApi\Generator\FbcGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class QueryBasedFbcContext implements FbcContextInterface
{
    use MainRequestTrait;

    private FbcContextInterface $decorated;

    private RequestStack $requestStack;

    private FbcGeneratorInterface $fbcGenerator;

    public function __construct(FbcContextInterface $decorated, RequestStack $requestStack, FbcGeneratorInterface $fbcGenerator)
    {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
        $this->fbcGenerator = $fbcGenerator;
    }

    public function getFbc(): ?string
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
        if (null === $request) {
            return $this->decorated->getFbc();
        }

        $facebookClickId = $request->query->get('fbclid');
        if (!is_string($facebookClickId) || '' === $facebookClickId) {
            return $this->decorated->getFbc();
        }

        return $this->fbcGenerator->generate($facebookClickId);
    }
}
