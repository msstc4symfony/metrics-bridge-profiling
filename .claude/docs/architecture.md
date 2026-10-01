# Архитектура

Мост — единственное место, где metrics и profiling знают друг о друге (сами бандлы друг от
друга не зависят; metrics 1.x держит устаревшую копию до 2.0).

| Слой (deptrac) | Что | Зависит от |
|---|---|---|
| `Metric` | `Enum\ProfilingMetric`, `Collector\SpanDurationCollector` | только metrics-bundle |
| `Processor` | `Processor\MetricEndSpanProcessor` | `Metric`, profiling-bundle |
| `Wiring` | бандл, `TakeOverProfilingMetricsPass` | всё |

Поток: `ProfilingFactory` → (очередь end-процессоров) → `MetricEndSpanProcessor::process()` →
`SpanDurationCollector::observe(message, getDuration())` → `AbstractCollector::observeHistogram`
(добавляет метки `application`, `component` из `metrics_bundle.applicationName/componentName`).

Подключение: `services.php` регистрирует коллектор (аргументы `$applicationName` /
`$componentName` явно — `bind` из metrics действует только на его сервисы) и процессор
(тег — через `#[AutoconfigureTag]` интерфейса profiling). Compiler pass проверяет, что
подключён MetricsBundle, дописывает enum в `metrics_bundle.metric_enums` (идемпотентно) и
снимает тег end-процессора с
`Msstc4Symfony\MetricsBundle\Framework\Profiling\Processor\EndSpan\MetricProcessor`.
