<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Provider;

use Setono\MetaConversionsApi\Pixel\Pixel;

interface PixelProviderInterface
{
    /**
     * Returns the applicable pixel(s) for the current request
     *
     * @return list<Pixel>
     */
    public function getPixels(): array;
}
