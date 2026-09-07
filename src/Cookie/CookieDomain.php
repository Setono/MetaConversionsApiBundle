<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Cookie;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Decides which domain the _fbp and _fbc cookies are written on, and the subdomain index that goes with it
 *
 * Meta encodes the level the cookie was set at in the second segment of the value: fb.1. means example.com,
 * fb.2. means www.example.com and fb.0. means a single label host or an ip address. Writing a host-only cookie
 * while claiming index 1 makes the two disagree, and apex and www then end up with different cookies
 *
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
 */
final class CookieDomain
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ?string $domain = null,
    ) {
    }

    /**
     * The domain to write the cookies on, or null to let the browser scope them to the current host
     */
    public function domain(): ?string
    {
        return $this->domain;
    }

    /**
     * The number of dots in the domain the cookie ends up on, which is how Meta's own parameter builder
     * computes it (`substr_count($etldPlus1, '.')`)
     */
    public function subdomainIndex(): int
    {
        $domain = $this->domain ?? $this->requestStack->getMainRequest()?->getHost();
        if (null === $domain || '' === $domain) {
            return 0;
        }

        // A leading dot is the classic way of writing a domain cookie and says nothing about the level
        return min(2, substr_count(ltrim($domain, '.'), '.'));
    }
}
