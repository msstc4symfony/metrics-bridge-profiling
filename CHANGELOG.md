# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow
[Semantic Versioning](https://semver.org/); dates are UTC.

## [2.0.0] - 2026-10-20

### Changed

- Requires the final 2.0 releases of metrics-bundle and profiling-bundle (`^2.0` instead of `^2.0@rc`).

## [2.0.0-rc.1] - 2026-10-20

### Changed

- Requires metrics-bundle ^2.0 and profiling-bundle ^2.0 (`^2.0@rc` until they are final): `ProfilingMetric`
  implements `Application\Metric\MetricDefinitionInterface`, `SpanDurationCollector` records through
  `MetricRecorderInterface` instead of extending the removed `AbstractCollector`, `MetricEndSpanProcessor` implements
  profiling's `Application\Processor\EndSpan\EndSpanProcessorInterface`.
- Classes moved into the canonical layout: `Enum\ProfilingMetric`, `Collector\SpanDurationCollector`,
  `Processor\MetricEndSpanProcessor` → `Infrastructure\Metrics\…`; `DependencyInjection\Compiler\RegisterProfilingMetricsPass`
  → `DependencyInjection\CompilerPass\…`. All but the bundle class and `ProfilingMetric` are `@internal`.
- A metric the recorder cannot write is logged to metrics-bundle's `metrics_bundle` channel and no longer fails the
  span. Metric name, labels and buckets are unchanged.
- PHPStan enforces `#[\Override]`; adopts bundle-standard v3 (canonical deptrac layers, anchored `BundleRoot`);
  declares `monolog/monolog` ^3.5 and `psr/log`, which it uses directly.
- Upgrading from 1.x: upgrade metrics-bundle and profiling-bundle to 2.0; the bridge has no configuration.

## [1.1.0] - 2026-10-14

### Changed

- Requires PHP 8.2 instead of 8.4 (Symfony 8 still needs PHP 8.4).
- Requires metrics-bundle ^1.4 and profiling-bundle ^1.1, their PHP 8.2 releases.

## [1.0.0] - 2026-10-04

First release of `msstc4symfony/metrics-bridge-profiling` (namespace `Msstc4Symfony\MetricsBridgeProfiling`).

### Added

- `MetricEndSpanProcessor` (an autoconfigured profiling `EndSpanProcessorInterface`) records the
  duration of every recorded span as an observation of the
  `profiling_span_duration_histogram_seconds` histogram, labelled `application`, `component` and
  `message` (span message lower-cased, characters outside `[a-z0-9_-]` replaced by `_`).
- `ProfilingMetric` is the only declaration of the metric; `RegisterProfilingMetricsPass` appends
  it to `msstc4symfony_metrics.metric_enums` and fails the build when MetricsBundle is not registered.
- Application and component names come from the `msstc4symfony_metrics` configuration.
- Extension alias `msstc4symfony_metrics_bridge_profiling`, no configuration tree of its own.

### Requirements

- PHP >= 8.4, Symfony ^7.4|^8.0, `msstc4symfony/metrics-bundle` ^1.0,
  `msstc4symfony/profiling-bundle` ^1.0.

[2.0.0]: https://github.com/msstc4symfony/metrics-bridge-profiling/releases/tag/v2.0.0
[2.0.0-rc.1]: https://github.com/msstc4symfony/metrics-bridge-profiling/releases/tag/v2.0.0-rc.1
[1.1.0]: https://github.com/msstc4symfony/metrics-bridge-profiling/releases/tag/v1.1.0
[1.0.0]: https://github.com/msstc4symfony/metrics-bridge-profiling/releases/tag/v1.0.0
