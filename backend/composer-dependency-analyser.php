<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    ->ignoreErrorsOnPackages(
        [
        ],
        [ErrorType::SHADOW_DEPENDENCY]
    )
    ->ignoreErrorsOnPackages(
        [
            'amphp/amp',
            'amphp/http-client',
            'monolog/monolog',
            'psr/log',
            'revolt/event-loop',
            'sentry/sentry',
        ],
        [ErrorType::UNUSED_DEPENDENCY]
    );
