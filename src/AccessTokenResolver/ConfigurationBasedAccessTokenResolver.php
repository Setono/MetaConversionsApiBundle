<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\AccessTokenResolver;

final class ConfigurationBasedAccessTokenResolver implements AccessTokenResolverInterface
{
    /**
     * Keyed by pixel id. Note that PHP casts numeric string keys to integers, and pixel ids are numeric, so this
     * is an array-key map rather than a string map. Lookups with the string id still resolve, because PHP applies
     * the same cast on the way in
     *
     * @var array<array-key, string>
     */
    private readonly array $accessTokens;

    /**
     * @param list<array{id: string, access_token?: string|null}> $pixels
     */
    public function __construct(array $pixels)
    {
        $accessTokens = [];

        foreach ($pixels as $pixel) {
            $accessToken = $pixel['access_token'] ?? null;
            if (null === $accessToken || '' === $accessToken) {
                continue;
            }

            $accessTokens[$pixel['id']] = $accessToken;
        }

        $this->accessTokens = $accessTokens;
    }

    public function resolve(string $pixelId): ?string
    {
        return $this->accessTokens[$pixelId] ?? null;
    }
}
