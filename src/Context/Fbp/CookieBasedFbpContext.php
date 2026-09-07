<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbp;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Setono\MetaConversionsApiBundle\Cookie\Cookies;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbpContext implements FbpContextInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly FbpContextInterface $decorated,
        private readonly RequestStack $requestStack,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function getFbp(): Fbp
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $this->decorated->getFbp();
        }

        $cookie = $request->cookies->get(Cookies::FBP);
        if (!is_string($cookie) || '' === $cookie) {
            return $this->decorated->getFbp();
        }

        try {
            return Fbp::fromString($cookie);
        } catch (\InvalidArgumentException $e) {
            // Falling back generates a brand new fbp, so from here on the value we send to Meta no longer matches
            // the one the browser holds. Silently degrading the match quality is worse than saying so
            $this->logger->debug('The _fbp cookie value "{value}" could not be parsed, so a new fbp was generated and the browser and server values no longer match: {message}', [
                'value' => $cookie,
                'message' => $e->getMessage(),
            ]);

            return $this->decorated->getFbp();
        }
    }
}
