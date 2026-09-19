# Форк laravel/mcp под PHP 8.1 и Laravel 10

Это форк [laravel/mcp](https://github.com/laravel/mcp), собранный под стек, который
не планируется поднимать: **PHP 8.1 + Laravel 10**. Апстрим выпилил PHP 8.1 в
v0.5.4 (04.02.2026) и поднял нижнюю границу до Laravel 11, поэтому 1.0 на таком
стеке не ставится.

Пакет называется `tdkomplekt/laravel-mcp`, namespace остался `Laravel\Mcp\` —
**документация апстрима применима дословно**: <https://laravel.com/docs/mcp>.

Ветка форка: `1.0.x-php81` (отведена от тега `v1.0.0`).
Поддержка Laravel 11/12/13 и PHP 8.2+ не сломана — ограничения только расширены вниз.

## Что изменено относительно апстрима

| Файл | Изменение |
|---|---|
| `composer.json` | `php: ^8.1`; в `illuminate/*` добавлен `^10.50`; в `symfony/process` добавлен `^6.4`; в `require-dev` добавлены `orchestra/testbench ^8.38`, `pestphp/pest ^2.36`, `guzzlehttp/guzzle ^7.8`; имя пакета `tdkomplekt/laravel-mcp` |
| `src/Support/Uri.php` | **новый файл** — замена `Illuminate\Support\Uri` (есть только с Laravel 11.35) |
| `src/Client/OAuth/OAuthClient.php` | импорт `Illuminate\Support\Uri` → `Laravel\Mcp\Support\Uri` (одна строка) |
| `src/Client/Transport/HttpTransport.php` | таймаут через `requestTimeout(): int` — в Laravel 10 `PendingRequest::timeout()` принимает только `int` |
| `rector.php` | набор правил `php81` вместо `php82` |
| `rector-downgrade.php` | **новый файл** — автоматический даунгрейд синтаксиса 8.2+ при ребейзе |
| `phpstan-laravel10.neon` + `phpstan-laravel10-baseline.neon` | **новые файлы** — профиль статического анализа под Laravel 10 |
| `.github/workflows/tests-php81.yml` | **новый файл** — CI-джоба PHP 8.1 / Laravel 10 |
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
        { "type": "vcs", "url": "https://github.com/TD-Komplekt/laravel-mcp.git" }
    ],
    "require": {
        "tdkomplekt/laravel-mcp": "1.0.0"
    }
}
```

Версия пинуется точно. Схема тегов: тег форка повторяет версию апстрима
(`v1.0.0` = апстримный `v1.0.0`), а правки, не связанные с апстримом,
получают четвёртую цифру — `v1.0.0.1`, `v1.0.0.2`.

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

4. **OAuth-поддержка пакета не работает с произвольными scope'ами** — это не
   ограничение форка, а осознанное решение апстрима: `Mcp::oauthRoutes()`
   объявляет и использует единственный scope `mcp:use`, OAuth служит только
   прослойкой к authenticatable-модели. Приложению со своей scope-моделью
   нужно оставлять собственные middleware и metadata-роуты.
