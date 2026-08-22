<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    ->ignoreErrorsOnPackages(
        [
        ],
        [ErrorType::SHADOW_DEPENDENCY]
    )
    ->ignoreErrorsOnExtensions(['ext-pdo_pgsql', 'ext-pdo_sqlite'], [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackages(
        [
            'amphp/amp',
            'amphp/http-client',
            'monolog/monolog',
            'psr/log',
            'revolt/event-loop',
            'sentry/sentry',
            'amphp/http-server',
            'amphp/http-server-router',
            'amphp/postgres',
            'thesis/clock',
            'thesis/message',
            'thesis/message-bus',
            'thesis/pgmq',
            'thesis/transaction',
        ],
        [ErrorType::UNUSED_DEPENDENCY]
    );
