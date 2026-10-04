# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

Runtime requirements: PHP >= 8.4, Symfony 7.4 / 8.x.

## Reporting a Vulnerability

Do **not** open public issues for security problems. Use:

1. **GitHub Security Advisory** (preferred):
   <https://github.com/msstc4symfony/metrics-bridge-profiling/security/advisories/new>
2. **Email**: `maxim.shamaev@gmail.com`

Include the bundle, PHP and Symfony versions, a reproducer and the impact. You will get an
acknowledgement within **7 days**; please allow 30–90 days before public disclosure.

## Threat Model

### 1. Span messages become label values

Every recorded span is exported as `profiling_span_duration_histogram_seconds` with the span
message (lower-cased, non-alphanumerics replaced by `_`) as the `message` label, on the
unauthenticated-by-default `GET /_/metrics` endpoint of metrics-bundle. Never put secrets,
personal data or unbounded values (ids, URLs) into span messages: they leak to whoever can
scrape the endpoint and every distinct value creates a new time series.

### 2. Cardinality

The number of series grows with the number of distinct span messages. Use span prefixes
(`msstc4symfony_profiling.spans.whitelist` / `blacklist`) to limit what is recorded.
