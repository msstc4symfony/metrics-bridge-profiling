# Архитектура

Мост — единственное место, где metrics и profiling знают друг о друге (сами бандлы друг от
друга не зависят). Метрика объявлена только здесь.

| Слой (deptrac) | Что | Зависит от |
|---|---|---|
| `Metric` | `Enum\ProfilingMetric`, `Collector\SpanDurationCollector` | только metrics-bundle |
| `Processor` | `Processor\MetricEndSpanProcessor` | `Metric`, profiling-bundle |
| `Wiring` | бандл, `RegisterProfilingMetricsPass` | всё |

Поток: `ProfilingFactory` → (очередь end-процессоров) → `MetricEndSpanProcessor::process()` →
`SpanDurationCollector::observe(message, round(getDuration(), 6))` →
`AbstractCollector::observeHistogram` (добавляет метки `application`, `component` из
`msstc4symfony_metrics.application_name/component_name`).

Подключение: `services.php` регистрирует коллектор (аргументы `$applicationName` /
`$componentName` явно — `bind` из metrics действует только на его сервисы) и процессор
(тег — через `#[AutoconfigureTag]` интерфейса profiling). Compiler pass проверяет, что
подключён MetricsBundle (есть параметр `msstc4symfony_metrics.metric_enums`), и идемпотентно
дописывает в него `ProfilingMetric`; `MetricRepositoryFactory` из metrics собирает каталог и
бросает `LogicException` при дубликате имени метрики. Алиас расширения —
`msstc4symfony_metrics_bridge_profiling`, дерева конфигурации нет.

## Не-final классы

Нет: все классы final.
