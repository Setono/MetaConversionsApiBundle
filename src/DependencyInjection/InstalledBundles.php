<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApiBundle\DependencyInjection;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

/**
 * The optional packages the bundle adapts to, in one place so the configuration and the extension cannot disagree
 * about what counts as installed
 */
final class InstalledBundles
{
    public const TAG_BAG_BUNDLE = 'setono/tag-bag-bundle';

    public const TAG_BAG_BUNDLE_CONSTRAINT = '^3.0';

    public static function hasTagBagBundle(): bool
    {
        return InstalledVersions::isInstalled(self::TAG_BAG_BUNDLE) &&
            InstalledVersions::satisfies(new VersionParser(), self::TAG_BAG_BUNDLE, self::TAG_BAG_BUNDLE_CONSTRAINT);
    }

    private function __construct()
    {
    }
}
