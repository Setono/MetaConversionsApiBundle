<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Tests\Double;

use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApiBundle\ConsentChecker\ConsentCheckerInterface;
use Setono\MetaConversionsApiBundle\Provider\PixelProviderInterface;

final class Doubles
{
    public static function consentChecker(bool $granted): ConsentCheckerInterface
    {
        return new class($granted) implements ConsentCheckerInterface {
            public function __construct(private readonly bool $granted)
            {
            }

            public function isGranted(): bool
            {
                return $this->granted;
            }
        };
    }

    /**
     * @param list<Pixel> $pixels
     */
    public static function pixelProvider(array $pixels): PixelProviderInterface
    {
        return new class($pixels) implements PixelProviderInterface {
            /**
             * @param list<Pixel> $pixels
             */
            public function __construct(private readonly array $pixels)
            {
            }

            public function getPixels(): array
            {
                return $this->pixels;
            }
        };
    }

    private function __construct()
    {
    }
}
