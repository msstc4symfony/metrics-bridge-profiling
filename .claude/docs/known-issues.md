# Известные особенности

- Метрика объявлена дважды, пока жив metrics 1.x: в `MetricLabelEnum` (deprecated) и в
  `ProfilingMetric`. metrics 1.2 схлопывает одинаковые имена в `MetricRepositoryFactory`
  (первое объявление побеждает) — `metrics:list` показывает её один раз. Определения обязаны
  совпадать: `BridgeTest::testMetricMatchesTheDefinitionItReplaces`.
- Без снятия тега с `MetricProcessor` каждый span писался бы дважды (тот же histogram, сумма
  удваивается) — ловит `KernelBridgeTest::testASpanIsRecordedOnceWithTheApplicationLabels`.
- Нормализация метки — копия metrics 1.x (`preg_replace` + `strtolower`), чтобы серии не
  поменялись при переходе на мост.
- metrics требует `ext-redis` — в CI-workflow он в `extensions`. Тесты используют
  `inmemory://` (ядро) и `Prometheus\Storage\InMemory` (unit).
- Для разработки против незакоммиченных изменений metrics/profiling — `composer-local.json`
  с path-репозиториями (`symlink: true`, в `.gitignore`).
- Compiler pass **снимает тег** `EndSpanProcessorInterface` с `MetricProcessor` (под любым id),
  а не удаляет сервис: ссылки приложения на старый процессор остаются валидными (ревью
  2026-10-01 UTC: `removeDefinition` ронял компиляцию при такой ссылке и пропускал процессор,
  зарегистрированный под другим id). Тег к этому моменту уже материализован
  `ResolveInstanceofConditionalsPass` (priority 100), итератор фабрики ещё не собран.
- `metrics_bundle.metric_enums` разрешается через `resolveValue` (ссылка на другой параметр);
  если на этапе компиляции это не массив (`%env(...)%`), pass бросает исключение вместо
  тихой перезаписи.
- Округление `round(..., 6)` — в процессоре, как у legacy `MetricProcessor`; коллектор пишет как
  есть, как legacy `ProfilingCollector` (паритет — data-provider тест против legacy-коллектора).
- `phpstan-deprecation-rules` не подключён (перепроверено 2026-10-02 UTC на Symfony 8.1, rules 2.0.5):
  5 находок, все неустранимы без baseline/ignore. 1 — `TestKernel::registerBundles()` наследует
  от `Kernel` Symfony 8.1 тип с deprecated `HttpKernel\Bundle\BundleInterface` (замена из
  `DependencyInjection\Kernel` не существует в 6.4/7.x). 4 — намеренные обращения к deprecated
  API metrics 1.x: `TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR` и паритет-тесты
  `BridgeTest` против `MetricLabelEnum`/`ProfilingCollector`. Пересмотреть с metrics 2.0
  (legacy уйдёт) и минимальным Symfony 8.1.
- path-репозитории в `composer-local.json` объявлены с `symlink: true`, но install идёт по
  `transport-options` из `composer-local.lock`: устаревший lock с `symlink: false` заставлял
  composer зеркалировать соседний бандл целиком и падать на чужих временных файлах
  (`.claude/.cc-writes`, `var/work/tmp/*` — «Unable to guess file type»). Лечится один раз:
  `COMPOSER=composer-local.json composer update msstc4symfony/metrics-bundle
  msstc4symfony/profiling-bundle` — lock получает `symlink: true`, в `vendor/` симлинки.
- Порядок первого релиза (2026-10-01 UTC): metrics и profiling публичные → metrics `v1.2.0` →
  `composer-ci.lock` с GitHub → push → CI → тег моста `v1.0.0`. Новые версии metrics/profiling
  попадают в мост через `COMPOSER=composer-ci.json composer update`.
- PHPStan `level: 10` (bundle-standard >= 1.8.0 допускает 9/10/max), baseline пуст.
- prefer-lowest (Symfony 6.4.0, symfony/error-handler 6.4.0): `KernelBridgeTest` ставит свой
  error handler до `boot()`, и `ErrorHandler::register()` из `FrameworkBundle::boot()` до
  error-handler 6.4.44 оставлял свой exception handler (PHPUnit: «did not remove its own
  exception handlers», risky → fail). Нижнюю границу не поднимали: error-handler транзитивный,
  в `require` верификатор допускает только `^6.4|^7.0|^8.0`, в `require-dev` CI перепинивает его
  в `6.4.*`, а `conflict` навязал бы ограничение пользователям ради чисто тестовой проблемы.
  Вместо этого `tearDown()` снимает exception handler'ы до сохранённого перед `boot()`.
- Infection в CI: `infection-min-msi`/`infection-min-covered-msi` = 93 (замер 97.7 − ~4).
- Отсутствие `trigger_deprecation` legacy-процессора при установленном мосте проверяет
  `KernelBridgeTest` своим error handler'ом (`@`-подавленные deprecation PHPUnit не видит).
