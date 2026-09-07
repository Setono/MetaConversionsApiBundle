<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Context\Fbc;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Symfony\Component\HttpFoundation\RequestStack;

final class CookieBasedFbcContext implements FbcContextInterface
{
    /**
     * Matches the _fbc cookie as Meta writes it today: fb.<subdomain index>.<creation time>.<click id> with an
     * optional trailing appendix segment.
     *
     * This is deliberately more lenient than Fbc::fromString() in the SDK, which only accepts alphanumeric click ids
     * and exactly four segments. Real click ids are base64url and contain - and _, and Meta's own parameter builder
     * (facebook/capi-param-builder-php) appends a 2 or 8 character appendix, so the strict pattern rejects cookies
     * that the browser pixel writes
     */
    private const COOKIE_PATTERN = '/^fb\.([012])\.(\d{13})\.([A-Za-z0-9_-]+)(?:\.([A-Za-z0-9_-]{2,8}))?$/';

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

        $cookie = $request->cookies->get('_fbc');
        if (!is_string($cookie) || '' === $cookie) {
            return null;
        }

        if (1 !== preg_match(self::COOKIE_PATTERN, $cookie, $matches)) {
            $this->logger->debug('The _fbc cookie value "{value}" could not be parsed and is ignored', ['value' => $cookie]);

            return null;
        }

        try {
            return (new Fbc($matches[3]))
                ->withSubdomainIndex((int) $matches[1])
                ->withCreationTime((int) $matches[2])
            ;
        } catch (\InvalidArgumentException $e) {
            // The creation time is in the future or predates Facebook
            $this->logger->debug('The _fbc cookie value "{value}" was rejected: {message}', [
                'value' => $cookie,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
