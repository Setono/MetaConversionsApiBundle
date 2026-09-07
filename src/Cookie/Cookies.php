<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\Cookie;

/**
 * The cookies Meta uses, shared by the contexts that read them and the subscribers that write them
 *
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
 */
final class Cookies
{
    public const FBP = '_fbp';

    public const FBC = '_fbc';

    /**
     * Meta keeps these for 90 days
     */
    public const LIFETIME = '+90 days';

    private function __construct()
    {
    }
}
