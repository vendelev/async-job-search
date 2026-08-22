<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withParallel()
    ->withCache(__DIR__ . '/storage/temp/rector')
    ->withImportNames()
    ->withPhpSets(php86: true)
    ->withPhpVersion(PhpVersion::PHP_86)
    ->withComposerBased(phpunit: true)
    ->withSets([
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::RECTOR_PRESET,
        SetList::INSTANCEOF,
        SetList::EARLY_RETURN
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        RemoveUnusedPublicMethodParameterRector::class,
        RemoveUselessParamTagRector::class,
        CatchExceptionNameMatchingTypeRector::class,
    ]);
