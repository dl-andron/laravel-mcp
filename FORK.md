# Форк laravel/mcp под PHP 8.1 и Laravel 10

Это форк [laravel/mcp](https://github.com/laravel/mcp), собранный под стек, который
не планируется поднимать: **PHP 8.1 + Laravel 10**. Апстрим выпилил PHP 8.1 в
v0.5.4 (04.02.2026) и поднял нижнюю границу до Laravel 11, поэтому 1.0 на таком
стеке не ставится.

Пакет называется `dl-andron/laravel-mcp`, namespace остался `Laravel\Mcp\` —
**документация апстрима применима дословно**: <https://laravel.com/docs/mcp>.

Ветка форка: `1.0.x-php81` (отведена от тега `v1.0.0`).
Поддержка Laravel 11/12/13 и PHP 8.2+ не сломана — ограничения только расширены вниз.

## Что изменено относительно апстрима

| Файл | Изменение |
|---|---|
| `composer.json` | `php: ^8.1`; в `illuminate/*` добавлен `^10.50`; в `symfony/process` добавлен `^6.4`; в `require-dev` добавлены `orchestra/testbench ^8.38`, `pestphp/pest ^2.36`, `guzzlehttp/guzzle ^7.8`; имя пакета `dl-andron/laravel-mcp`; `config.audit.ignore` на одну advisory (см. «Известные ограничения», п. 4) |
| `src/Support/Uri.php` | **новый файл** — замена `Illuminate\Support\Uri` (есть только с Laravel 11.35) |
| `src/Client/OAuth/OAuthClient.php` | импорт `Illuminate\Support\Uri` → `Laravel\Mcp\Support\Uri` (одна строка) |
| `src/Client/Transport/HttpTransport.php` | таймаут через `requestTimeout(): int` — в Laravel 10 `PendingRequest::timeout()` принимает только `int` |
| `rector.php` | набор правил `php81` вместо `php82` |
| `rector-downgrade.php` | **новый файл** — автоматический даунгрейд синтаксиса 8.2+ при ребейзе |
| `phpstan-laravel10.neon` + `phpstan-laravel10-baseline.neon` | **новые файлы** — профиль статического анализа под Laravel 10 |
| `.github/workflows/tests-php81.yml` | **новый файл** — CI-джоба PHP 8.1 / Laravel 10 |
| `.github/workflows/coding-standards.yml` | триггер `on: [push]` → только ветки: на пуш тега workflow падал, потому что не может закоммитить правки стиля в тег |
| `tests/Fixtures/PassportClient.php` | `casts()` → свойство `$casts` (метод появился в Laravel 11) |
| `tests/Feature/Client/OAuthCallbackRouteTest.php`, `tests/Unit/Server/RegistrarTest.php` | убран standalone-тип `null` в `fn (): null` (синтаксис PHP 8.2) |
| `tests/Feature/Testing/Tools/AssertStructuredContentTest.php` | один тест пропускается на Laravel < 11 (см. «Известные ограничения») |

Почему изменений так мало: в `src/` апстрима **нет ни одной конструкции PHP 8.2+**
(проверено `php8.1 -l` по всем 175 файлам), а из классов фреймворка недоступен на
Laravel 10 был ровно один — `Illuminate\Support\Uri`.
Контракт `Illuminate\Contracts\JsonSchema\JsonSchema` Laravel забэкпортил в 10.50,
а пакет `illuminate/json-schema` в ветке 12.x сам требует `php ^8.1`.

## Установка

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/dl-andron/laravel-mcp.git" }
    ],
    "require": {
        "dl-andron/laravel-mcp": "1.0.0.2"
    }
}
```

Версия пинуется точно.

**Дефолтная ветка репозитория — `1.0.x-php81`, и это обязательное условие.**
Имя пакета Composer берёт из `composer.json` дефолтной ветки: пока ею была `main`
с апстримным содержимым, весь репозиторий индексировался как `laravel/mcp`, а
`dl-andron/laravel-mcp` не находился вообще. Апстримные теги при этом молча
пропускаются — в их `composer.json` другое имя (предупреждения печатаются только
в verbose-режиме).

**Схема тегов.** Теги апстрима (`v1.0.0`, `v1.0.1`, …) в репозитории сохраняются —
они нужны для ребейза и указывают на апстримные коммиты **без бэкпорта**.
Релизы форка всегда четырёхзначные: `vX.Y.Z.N`, где `X.Y.Z` — релиз апстрима, на
который отребейзен форк, а `N` — ревизия форка, начиная с 1. Текущий релиз —
**`v1.0.0.2`** (апстримный 1.0.0 + бэкпорт). Требовать в проекте нужно именно
четырёхзначную версию: `1.0.0` отдаст апстримный код без правок.

## Проверка на целевом стеке

```bash
composer test:php81
```

Запускает `rector --dry-run`, полный прогон Pest и PHPStan с профилем Laravel 10.
Ожидаемый результат: `1248 passed, 1 skipped`, `[OK] No errors`.

Pint в профиль **не входит**: последняя версия pint, работающая на PHP 8.1 —
1.20, и она форматирует иначе, чем актуальная 1.32. Прогон `pint` на этом профиле
переформатировал бы весь репозиторий и сделал бы каждый ребейз неподъёмным.
Стиль проверяется апстримным `coding-standards.yml` на свежем PHP.

## Ребейз на новый релиз апстрима

```bash
git fetch upstream
git rebase upstream/main            # или на конкретный тег
vendor/bin/rector process --config=rector-downgrade.php   # синтаксис 8.2+ → 8.1
composer update
composer test:php81
```

Дополнительно, если апстрим начал использовать классы фреймворка из Laravel 11+,
их удобно ловить скриптом-проверкой: собрать все `use Illuminate\...` из `src/`
и прогнать `class_exists()` на установленном Laravel 10 — это находит проблему
за секунды, до запуска тестов.

Триггер пересмотреть форк: апстрим начал опираться на API Laravel 11/12/13
(`Context`, `Uri`, container-атрибуты, `defer()` и т.п.) не точечно, а массово.

## Известные ограничения на Laravel 10

1. **`AssertableJson::where()` не разворачивает BackedEnum.** Conditional-логика
   для enum'ов появилась в Laravel 11, поэтому в `assertStructuredContent()`
   сравнивать нужно со значением:

   ```php
   ->where('status', BookingStatus::Confirmed->value)   // Laravel 10
   ->where('status', BookingStatus::Confirmed)          // Laravel 11+
   ```

   Ровно один тест апстрима помечен `skip()` на Laravel < 11.

2. **Дробные таймауты HTTP-клиента округляются вверх.** `PendingRequest::timeout()`
   в Laravel 10 принимает только `int`, поэтому `setTimeoutSeconds(0.5)`
   превращается в 1 секунду. На Laravel 11+ поведение апстрима не меняется
   только по типу — округление применяется всегда, см. `HttpTransport::requestTimeout()`.

3. **PHPStan на Laravel 10 даёт 19 замечаний, которых нет на 11+.** Они все об
   одном: у хелперов `response()`, `view()` и `url()` в Laravel 10 union-типы без
   conditional return types, поэтому `response()->json()` выглядит как вызов
   несуществующего метода. Зафиксированы в `phpstan-laravel10-baseline.neon`;
   любое новое замечание по-прежнему валит анализ.

4. **PHPUnit на профиле 8.1 зафиксирован на 10.5.36 с известной advisory.**
   Pest 2.36.0 — последний Pest, работающий на PHP 8.1 — объявляет
   `conflict: phpunit >10.5.36`, то есть допускает ровно одну версию PHPUnit.
   А Composer 2.10+ по умолчанию **исключает из резолвинга версии с известными
   уязвимостями**, и advisory `PKSA-z3gr-8qht-p93v` («Unsafe Deserialization in
   PHPT Code Coverage Handling») покрывает `phpunit >=10.0.0,<10.5.62`. В итоге
   на свежем Composer профиль 8.1 не собирался вообще.

   Решение — точечный `config.audit.ignore` на эту одну advisory в `composer.json`
   (не общий `--no-blocking`, остальные блокировки работают). Это осознанный
   компромисс: PHPUnit — dev-зависимость, в прод не уезжает, уязвимость касается
   обработки PHPT-покрытия. Запись нужно убрать, как только выйдет Pest под
   PHP 8.1, допускающий `phpunit >=10.5.62`.

   Локальный Composer 2.7.x эту блокировку не делает и собирает профиль без
   записи — расхождение с CI проявляется только на Composer ≥ 2.9.

5. **OAuth-поддержка пакета не работает с произвольными scope'ами** — это не
   ограничение форка, а осознанное решение апстрима: `Mcp::oauthRoutes()`
   объявляет и использует единственный scope `mcp:use`, OAuth служит только
   прослойкой к authenticatable-модели. Приложению со своей scope-моделью
   нужно оставлять собственные middleware и metadata-роуты.
