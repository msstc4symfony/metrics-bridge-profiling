# Changelog

All notable changes to this bundle are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow
[Semantic Versioning](https://semver.org/); dates are UTC.

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

[1.0.0]: https://github.com/msstc4symfony/metrics-bridge-profiling/releases/tag/v1.0.0
