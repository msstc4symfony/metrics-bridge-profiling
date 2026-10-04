# Известные особенности

- Метрика объявлена только в `ProfilingMetric`; metrics-bundle её не знает. Дубликат имени в
  любом enum каталога metrics — `LogicException` при сборке репозитория.
- Нормализация метки — `SpanDurationCollector::normalizeLabel()` (`private`), округление
  `round(..., 6)` — только в `MetricEndSpanProcessor`; коллектор пишет значение как есть.
- `KernelBridgeTest` страхуется от deprecation-уведомлений бандлов msstc4symfony своим error
  handler'ом: `trigger_deprecation()` глушит `@`, PHPUnit такие не видит.
- Siblings подключены как `^1.0` (vcs-репозитории по https в `composer.json` / `composer-ci.json`);
  в `composer-local.json` path-репозитории с версией `1.0.0`.
- prefer-lowest (Symfony 7.4.0): `FrameworkBundle` 7.4.0 / `symfony/error-handler` < 7.4.17 оставляет
  exception handler после `boot()` при чужом активном error handler'е (PHPUnit: «did not remove its own
  exception handlers», risky). CI-профиль объявляет `conflict` `symfony/error-handler: <7.4.17 || >=8.0,<8.1.5` (общий для всех бандлов)
  (только в `composer-ci.json`, пользователям не навязывается); `tearDown()` дополнительно снимает
  handler'ы через `Test\Unit\Fixture\ExceptionHandlerSnapshot`: если сохранённого handler'а в стеке
  уже нет, снятые возвращаются обратно. Стек exception handler'ов в PHP не читается напрямую — текущий
  берётся через `set_exception_handler(null)` + `restore_exception_handler()`.
- metrics требует `ext-redis` — в CI-workflow он в `extensions`. Тесты используют
  `inmemory://` (ядро) и `Prometheus\Storage\InMemory` (unit).
- Для разработки против незакоммиченных изменений metrics/profiling — `composer-local.json`
  с path-репозиториями (`symlink: true`, в `.gitignore`).
- path-репозитории в `composer-local.json` объявлены с `symlink: true`, но install идёт по
  `transport-options` из `composer-local.lock`: устаревший lock с `symlink: false` заставлял
  composer зеркалировать соседний бандл целиком и падать на чужих временных файлах
  (`.claude/.cc-writes`, `var/work/tmp/*` — «Unable to guess file type»). Лечится один раз:
  `COMPOSER=composer-local.json composer update msstc4symfony/metrics-bundle
  msstc4symfony/profiling-bundle` — lock получает `symlink: true`, в `vendor/` симлинки.
- PHPStan `level: 10` (bundle-standard допускает 9/10/max), baseline пуст.
- Ревью перехода на шаблоны bundle-standard (2026-10-02 UTC), отклонено/вынесено в bundle-standard: `Makefile`,
  `phpunit.xml.dist` и `phpstan-ci.neon` — `ExactFileRule` верификатора (байт-в-байт с шаблонами
  bundle-standard), локальная правка ломает `verify-standard`. Поэтому здесь не правились:
  (а) подсказки `test`/`test-integration` про самопропуск интеграционных тестов и
  «needs the composer-ci.json install» неверны для этого бандла (всё нужное уже в `require-dev`);
  (б) `.PHONY` перечисляет только `help`; (в) комментарий у `<source>` в `phpunit.xml.dist`
  обещает больше, чем `failOnDeprecation` ловит: `trigger_deprecation()` глушит `@`, а
  `ignoreSuppressionOfDeprecations` выключен — deprecation бандлов по-прежнему ловит свой
  error handler `KernelBridgeTest`; (г) комментарий `phpstan-ci.neon` про `class.notFound` в
  baseline устарел (baseline пуст). Всё — follow-up в шаблоны bundle-standard.
- Infection в CI: `infection-min-msi`/`infection-min-covered-msi` = 93 (замер 97.7 − ~4).
