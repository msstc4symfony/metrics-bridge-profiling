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
- `phpstan-deprecation-rules` не подключён: на Symfony 8.1 он ругается на `BundleInterface` в
  сигнатуре `registerBundles()` самого фреймворка.
- path-репозиторий в `composer-local.json` зеркалирует каталог бандла целиком и спотыкается
  о служебные файлы песочницы в `.claude/` соседнего репо (пакет остаётся без `src/`).
  Лечится удалением `vendor/msstc4symfony` и `composer install` заново.
- Порядок первого релиза (2026-10-01 UTC): metrics и profiling публичные → metrics `v1.2.0` →
  `composer-ci.lock` с GitHub → push → CI → тег моста `v1.0.0`. Новые версии metrics/profiling
  попадают в мост через `COMPOSER=composer-ci.json composer update`.
- PHPStan `level: 9`, хотя код чист на 10: верификатор стандарта требует строку `level: 9`.
- Отсутствие `trigger_deprecation` legacy-процессора при установленном мосте проверяет
  `KernelBridgeTest` своим error handler'ом (`@`-подавленные deprecation PHPUnit не видит).
