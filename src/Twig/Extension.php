<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            //new TwigFunction('meta_conversions_api_init', [Runtime::class, 'getPagePreviewLinks'], ['is_safe' => ['html']]),
        ];
    }
}
