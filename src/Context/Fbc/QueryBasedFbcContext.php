<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Component\HttpFoundation\RequestStack;

final class QueryBasedFbcContext implements FbcContextInterface
{
    /**
     * Facebook click ids are base64url encoded, so this accepts the alphabet Meta uses and nothing else.
     *
     * The value ends up in a cookie and in the payload sent to Meta, and it is fully controlled by whoever
     * crafted the link the visitor followed, so it must not be taken at face value
     */
    private const CLICK_ID_PATTERN = '/^[A-Za-z0-9_-]{1,255}$/';

    public function __construct(
        private readonly FbcContextInterface $decorated,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getFbc();
        }

        $facebookClickId = $request->query->get('fbclid');
        if (!is_string($facebookClickId) || 1 !== preg_match(self::CLICK_ID_PATTERN, $facebookClickId)) {
            return $this->decorated->getFbc();
        }

        return new Fbc($facebookClickId);
    }
}
