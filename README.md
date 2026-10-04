# Metrics bridge for the Profiling bundle

![Build Status](https://github.com/msstc4symfony/metrics-bridge-profiling/actions/workflows/checks.yml/badge.svg?branch=main)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Exports the duration of every span recorded by
[`msstc4symfony/profiling-bundle`](https://github.com/msstc4symfony/profiling-bundle) as a
Prometheus histogram of
[`msstc4symfony/metrics-bundle`](https://github.com/msstc4symfony/metrics-bundle):

```
symfony_profiling_span_duration_histogram_seconds{application, component, message}
```

`message` is the span message, lower-cased, with everything but `[a-z0-9_-]` replaced by `_`
(`request orders_list` → `request_orders_list`). The duration is the one fixed by
`SpanInterface::end()`. Buckets: 0.1, 0.5, 1, 3, 5, 10, 15, 30, 60, 180 seconds.

## Compatibility

| Bridge | PHP  | Symfony       | metrics-bundle | profiling-bundle |
|--------|------|---------------|----------------|------------------|
| 1.x    | 8.4+ | 7.4, 8.x      | ^1.0           | ^1.0             |

## Installation

```sh
composer config repositories.msstc4symfony-metrics vcs https://github.com/msstc4symfony/metrics-bundle
composer config repositories.msstc4symfony-profiling vcs https://github.com/msstc4symfony/profiling-bundle
composer config repositories.msstc4symfony-metrics-bridge-profiling vcs https://github.com/msstc4symfony/metrics-bridge-profiling
composer require msstc4symfony/metrics-bridge-profiling
```

Register the three bundles in `config/bundles.php`:

```php
Msstc4Symfony\MetricsBundle\MetricsBundle::class => ['all' => true],
Msstc4Symfony\ProfilingBundle\ProfilingBundle::class => ['all' => true],
Msstc4Symfony\MetricsBridgeProfiling\MetricsBridgeProfilingBundle::class => ['all' => true],
```

Nothing to configure: what gets recorded is decided by the profiling-bundle configuration
(routes, commands, messages, span prefixes).

## Cardinality

Every distinct span message is a new time series. Keep messages low-cardinality
(`request orders_show`, not `request /orders/42`); put ids into the span context, which is
never exported.

## Local development

`composer-ci.json` installs metrics-bundle and profiling-bundle from their GitHub repositories.

```sh
COMPOSER=composer-ci.json composer install
COMPOSER=composer-ci.json make check
make test
make fix
```

## License

MIT, see [LICENSE](LICENSE).
