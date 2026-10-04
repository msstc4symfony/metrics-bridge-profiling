# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4symfony/metrics-bridge-profiling`, namespace
`Msstc4Symfony\MetricsBridgeProfiling`) that connects `msstc4symfony/profiling-bundle` to
`msstc4symfony/metrics-bundle`: every recorded span becomes an observation of the
`profiling_span_duration_histogram_seconds` histogram. PHP >= 8.4, Symfony 7.4 / 8.x.

## Common commands

Develop against the CI profile: `COMPOSER=composer-ci.json composer install`.

- `make check` — `php -l`, PHPStan level 10, PHP-CS-Fixer, `composer validate --strict`,
  `composer audit`, Rector dry-run, deptrac. Run as `COMPOSER=composer-ci.json make check`.
- `make test` — unit + integration suites; `make fix`.

## Architecture in 60 seconds

- `Enum\ProfilingMetric` — the single declaration of the metric (name, labels, buckets).
- `Collector\SpanDurationCollector` (extends metrics' `AbstractCollector`) normalises the span
  message into the `message` label (`normalizeLabel()`, the only place that does it).
- `Processor\MetricEndSpanProcessor` — profiling `EndSpanProcessorInterface`, autoconfigured;
  records `round(getDuration(), 6)` (the only place that rounds).
- `DependencyInjection\Compiler\RegisterProfilingMetricsPass` appends the enum to the
  `msstc4symfony_metrics.metric_enums` container parameter; without MetricsBundle it throws.
- Extension alias: `msstc4symfony_metrics_bridge_profiling` (no configuration tree).

Details: `.claude/docs/architecture.md`; gotchas: `.claude/docs/known-issues.md`.
