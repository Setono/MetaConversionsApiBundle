<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbcContext implements FbcContextInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly RequestStack $requestStack,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function getFbc(): ?Fbc
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        $cookie = $request->cookies->get(Cookies::FBC);
        if (!is_string($cookie) || '' === $cookie) {
            return null;
        }

        try {
            return Fbc::fromString($cookie);
        } catch (\InvalidArgumentException $e) {
            // Previously this was swallowed, which made a cookie the bundle could not read look exactly like a
            // visitor who never clicked an ad
            $this->logger->debug('The _fbc cookie value "{value}" could not be parsed and is ignored: {message}', [
                'value' => $cookie,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
