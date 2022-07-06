<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbpContext implements FbpContextInterface
{
    use MainRequestTrait;

    private FbpContextInterface $decorated;

    private RequestStack $requestStack;

    public function __construct(FbpContextInterface $decorated, RequestStack $requestStack)
    {
        $this->decorated = $decorated;
        $this->requestStack = $requestStack;
    }

    public function getFbp(): Fbp
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
        if (null === $request) {
            return $this->decorated->getFbp();
        }

        $fbp = $request->cookies->get('_fbp');
        if (is_string($fbp)) {
            try {
                return Fbp::fromString($fbp);
            } catch (\InvalidArgumentException $e) {
            }
        }

        return $this->decorated->getFbp();
    }
}
