<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\AccessTokenResolver;

interface AccessTokenResolverInterface
{
    /**
     * Returns the Conversions API access token for the given pixel, or null when there is none
     *
     * This is called when the event is sent, which may be in a worker process long after the request that raised
     * it, so the implementation must not depend on the current request
     */
    public function resolve(string $pixelId): ?string;
}
