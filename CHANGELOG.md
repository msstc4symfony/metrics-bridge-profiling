# Changelog

## 1.1.0

- CI on bundle-standard 1.8.0: PHPStan level 10, blocking Roave BC check, Infection with a
  93 % minimum MSI, and a `--prefer-lowest` PHPUnit cell (Symfony 6.4.0).
- Kernel integration tests restore the exception handler that `FrameworkBundle::boot()` leaves
  behind on symfony/error-handler < 6.4.44. No runtime changes.

## 1.0.0

First release. Exports profiling-bundle span durations as the metrics-bundle histogram
`profiling_span_duration_histogram_seconds` (same definition as metrics-bundle 1.x), using the
duration fixed at `end()`, and replaces metrics-bundle's deprecated built-in span processor.
