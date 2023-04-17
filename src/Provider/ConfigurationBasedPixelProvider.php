<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Provider;

use Setono\MetaConversionsApi\Pixel\Pixel;

final class ConfigurationBasedPixelProvider implements PixelProviderInterface
{
    /** @var list<array{id: string, access_token?: string}> */
    private array $pixels;

    /**
     * @param list<array{id: string, access_token?: string}> $pixels
     */
    public function __construct(array $pixels)
    {
        // this will filter all pixels where the id _or_ the access_token is empty
        $this->pixels = array_values(array_filter($pixels, static function (array $pixel): bool {
            return '' !== $pixel['id'];
        }));
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
