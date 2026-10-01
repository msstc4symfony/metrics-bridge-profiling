# Changelog

## 1.0.0

First release. Exports profiling-bundle span durations as the metrics-bundle histogram
`profiling_span_duration_histogram_seconds` (same definition as metrics-bundle 1.x), using the
duration fixed at `end()`, and replaces metrics-bundle's deprecated built-in span processor.
