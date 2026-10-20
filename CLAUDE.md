# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4symfony/metrics-bridge-profiling`, namespace
`Msstc4Symfony\MetricsBridgeProfiling`) that connects `msstc4symfony/profiling-bundle` to
`msstc4symfony/metrics-bundle`: every recorded span becomes an observation of the
`profiling_span_duration_histogram_seconds` histogram. PHP >= 8.2, Symfony 7.4 / 8.x.

## Common commands

Develop against the CI profile: `COMPOSER=composer-ci.json composer install`.

- `make check` — `php -l`, PHPStan level 10, PHP-CS-Fixer, `composer validate --strict`,
  `composer audit`, Rector dry-run, deptrac. Run as `COMPOSER=composer-ci.json make check`.
- `make test` — unit + integration suites; `make fix`.

## Architecture in 60 seconds

- `Infrastructure\Metrics\ProfilingMetric` — the single declaration of the metric (name, labels, buckets); public API.
- `Infrastructure\Metrics\SpanDurationCollector` (`@internal`) records through metrics 2.0's `MetricRecorderInterface`,
  normalises the span message into the `message` label (`normalizeLabel()`, the only place that does it) and logs
  any recorder failure to the `metrics_bundle` channel.
- `Infrastructure\Metrics\MetricEndSpanProcessor` (`@internal`) — profiling 2.0 `EndSpanProcessorInterface`,
  autoconfigured; records `round(getDuration(), 6)` (the only place that rounds).
- `DependencyInjection\CompilerPass\RegisterProfilingMetricsPass` appends the enum to the
  `msstc4symfony_metrics.metric_enums` container parameter; without MetricsBundle it throws.
- Extension alias: `msstc4symfony_metrics_bridge_profiling` (no configuration tree).

Details: `.claude/docs/architecture.md`; gotchas: `.claude/docs/known-issues.md`.
