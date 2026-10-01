# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4symfony/metrics-bridge-profiling`, namespace
`Msstc4Symfony\MetricsBridgeProfiling`) that connects `msstc4symfony/profiling-bundle` to
`msstc4symfony/metrics-bundle`: every recorded span becomes an observation of the
`profiling_span_duration_histogram_seconds` histogram. PHP >= 8.4, Symfony 6.4 / 7.x / 8.x.

## Common commands

Develop against the CI profile: `COMPOSER=composer-ci.json composer install`.

- `make check` — `php -l`, PHPStan level 9, PHP-CS-Fixer, `composer validate --strict`,
  `composer audit`, Rector dry-run, deptrac. Run as `COMPOSER=composer-ci.json make check`.
- `make test` — unit + integration suites; `make fix`.

## Architecture in 60 seconds

- `Enum\ProfilingMetric` — the metric definition, identical to metrics-bundle 1.x's
  deprecated `MetricLabelEnum::PROFILING_SPAN_DURATION_HISTOGRAM_SECONDS` (a test guards it).
- `Collector\SpanDurationCollector` (extends metrics' `AbstractCollector`) normalises the span
  message into the `message` label.
- `Processor\MetricEndSpanProcessor` — profiling `EndSpanProcessorInterface`, autoconfigured;
  records `getDuration()`.
- `DependencyInjection\Compiler\TakeOverProfilingMetricsPass` appends the enum to
  `metrics_bundle.metric_enums` and clears the end-processor tag of metrics' deprecated
  `MetricProcessor` (the service stays for application references).

Details: `.claude/docs/architecture.md`; gotchas: `.claude/docs/known-issues.md`.
