<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;

/**
 * Автоматический даунгрейд синтаксиса PHP 8.2+ до 8.1.
 *
 * Это инструмент для ребейза форка на новый релиз апстрима, а не часть
 * обычной сборки: апстрим собирается под PHP 8.2 и рано или поздно начнёт
 * использовать синтаксис, который не парсится на 8.1 (readonly-классы,
 * standalone-типы null/false/true, константы в трейтах, DNF-типы).
 *
 *   git rebase upstream/main
 *   vendor/bin/rector process --config=rector-downgrade.php
 *   composer test:php81
 *
 * Отдельный конфиг, а не rector.php, потому что наборы upgrade и downgrade
 * в одном прогоне конфликтуют друг с другом.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/workbench',
    ])
    ->withSets([
        DowngradeLevelSetList::DOWN_TO_PHP_81,
    ]);
