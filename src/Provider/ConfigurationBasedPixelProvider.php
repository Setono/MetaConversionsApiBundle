<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Provider;

use Setono\MetaConversionsApi\Pixel\Pixel;

final class ConfigurationBasedPixelProvider implements PixelProviderInterface
{
    /** @var list<array{id: string, access_token?: string|null}> */
    private readonly array $pixels;

    /**
     * @param list<array{id: string, access_token?: string|null}> $pixels
     */
    public function __construct(array $pixels)
    {
        // A pixel without an id is useless, both client and server side. An empty access token is kept:
        // client side tracking does not need one, and the send handler logs and skips such a pixel
        $this->pixels = array_values(array_filter($pixels, static fn (array $pixel): bool => '' !== $pixel['id']));
    }

    public function getPixels(): array
    {
        $pixels = [];
        foreach ($this->pixels as $pixel) {
            $pixels[] = new Pixel($pixel['id'], $pixel['access_token'] ?? null);
        }

        return $pixels;
    }
}
