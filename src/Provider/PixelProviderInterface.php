<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Provider;

use Setono\MetaConversionsApi\Pixel\Pixel;

interface PixelProviderInterface
{
    /**
     * Returns the applicable pixel(s)
     *
     * This is called while a request is handled, to decide which pixels an event goes to, and again when the event is
     * sent, to get the access tokens, which never travel with the queued event. The second call may happen in a
     * worker, where there is no request, so it has to return the pixels and their access tokens then too
     *
     * @return list<Pixel>
     */
    public function getPixels(): array;
}
