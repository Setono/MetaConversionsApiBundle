<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('setono/consent-contracts', [ErrorType::SHADOW_DEPENDENCY]) // Optional dependency
    ->ignoreErrorsOnPackage('setono/tag-bag', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // Optional dependency
;
