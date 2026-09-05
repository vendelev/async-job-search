<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    ->ignoreErrorsOnPackages(
        [
            'typhoon/type'
        ],
        [ErrorType::SHADOW_DEPENDENCY]
    )
    ->ignoreErrorsOnPackages(
        [
            'revolt/event-loop',
            'sentry/sentry',
            'thesis/message-bus',
            'thesis/pgmq',
            'thesis/transaction',
        ],
        [ErrorType::UNUSED_DEPENDENCY]
    );
