<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveDeadInstanceOfAssertRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        ReadOnlyPropertyRector::class,
        NewlineBetweenClassLikeStmtsRector::class,
        StringClassNameToClassConstantRector::class => [
            __DIR__.'/src/Server/Http/Controllers/OAuthRegisterController.php',
        ],
        RemoveDeadInstanceOfAssertRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
        // Форк собирается под PHP 8.1, поэтому набор правил — php81, а не php82:
        // иначе Rector предлагал бы синтаксис, который не парсится на 8.1.
    )->withPhpSets(php81: true);
