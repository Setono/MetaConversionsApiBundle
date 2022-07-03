<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Provider;

use Setono\MetaConversionsApi\Pixel\Pixel;

final class ConfigurationBasedPixelProvider implements PixelProviderInterface
{
    /** @var list<array{id: string, access_token: string}> */
    private array $pixels;

    /**
     * @param list<array{id: string, access_token: string}> $pixels
     */
    public function __construct(array $pixels)
    {
        $this->pixels = $pixels;
    }

    public function getPixels(): array
    {
        $pixels = [];
        foreach ($this->pixels as $pixel) {
            $pixels[] = new Pixel($pixel['id'], $pixel['access_token']);
        }

        return $pixels;
    }
}
