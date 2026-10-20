# Архитектура

Мост — единственное место, где metrics и profiling знают друг о друге (сами бандлы друг от
друга не зависят). Метрика объявлена только здесь.

| Слой (deptrac, шаблон v3) | Что | Зависит от |
|---|---|---|
| `Infrastructure` | `Infrastructure\Metrics\{ProfilingMetric, SpanDurationCollector, MetricEndSpanProcessor}` | только vendor (metrics, profiling) |
| `DependencyInjection` | `CompilerPass\RegisterProfilingMetricsPass` | `Infrastructure` |
| `BundleRoot` | бандл | `DependencyInjection` |

Поток: `ProfilingFactory` → (очередь end-процессоров) → `MetricEndSpanProcessor::process()` →
`SpanDurationCollector::observe(message, round(getDuration(), 6))` → `MetricRecorderInterface::observe()` metrics 2.0
(адаптер Prometheus добавляет метки `application`, `component`). Сбой порта (неподходящий тип или исключение чужой
реализации) логируется в канал `metrics_bundle` (`#[WithMonologChannel]`) и не роняет span.

Подключение: `services.php` регистрирует коллектор (автовайринг порта и логгера) и процессор
(тег — через `#[AutoconfigureTag]` интерфейса profiling). Compiler pass проверяет, что
подключён MetricsBundle (есть параметр `msstc4symfony_metrics.metric_enums`), и идемпотентно
дописывает в него `ProfilingMetric`; `MetricRepositoryFactory` из metrics собирает каталог и
бросает `LogicException` при дубликате имени метрики. Алиас расширения —
`msstc4symfony_metrics_bridge_profiling`, дерева конфигурации нет. `@internal`: коллектор, процессор, pass.
Unit-тесты — на `tests/Fixtures/RecordingMetricRecorder` (порт), kernel-тест — настоящий registry.

## Не-final классы

Нет: все классы final.
