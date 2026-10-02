# Changelog

## 1.1.1

- Kernel integration tests restore exception handlers through `ExceptionHandlerSnapshot`: when the
  handler saved before `boot()` is gone, the stack is put back instead of stripping PHPUnit's own.
- The affected symfony/error-handler range is every release before the "another error handler is
  in charge" fix (6.4.44 and the matching 7.x/8.x patches), not only `< 6.4.44`. No runtime changes.

## 1.1.0

- CI on bundle-standard 1.8.0: PHPStan level 10, blocking Roave BC check, Infection with a
  93 % minimum MSI, and a `--prefer-lowest` PHPUnit cell (Symfony 6.4.0).
- Tooling from the 1.8.0 templates: PHPUnit `failOnDeprecation` with `ignoreIndirectDeprecations`,
  Makefile targets `test-unit`, `test-integration` and `install-ci`.
- Kernel integration tests restore the exception handler that `FrameworkBundle::boot()` leaves
  behind on symfony/error-handler before the "another error handler is in charge" fix (6.4.44 and
  the matching 7.x/8.x patches). No runtime changes.

## 1.0.0

First release. Exports profiling-bundle span durations as the metrics-bundle histogram
`profiling_span_duration_histogram_seconds` (same definition as metrics-bundle 1.x), using the
duration fixed at `end()`, and replaces metrics-bundle's deprecated built-in span processor.
