<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics;

use Monolog\Attribute\WithMonologChannel;
use Msstc4Symfony\MetricsBundle\Application\LogChannel;
use Msstc4Symfony\MetricsBundle\Application\Port\MetricRecorderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logs to metrics-bundle's channel: the application's error counter does not count it.
 *
 * @internal
 */
#[WithMonologChannel(LogChannel::NAME)]
final readonly class SpanDurationCollector
{
    public function __construct(
        private MetricRecorderInterface $recorder,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function observe(string $spanMessage, float $seconds): void
    {
        try {
            $this->recorder->observe(ProfilingMetric::SPAN_DURATION, $seconds, [$this->normalizeLabel($spanMessage)]);
        } catch (Throwable $exception) {
            // The recorder logs storage failures itself; a definition it cannot use must not end the span with an error.
            $this->reportFailure($spanMessage, $exception);
        }
    }

    private function reportFailure(string $spanMessage, Throwable $exception): void
    {
        try {
            $this->logger?->error('Could not record the span metric', [
                'metric' => ProfilingMetric::SPAN_DURATION->value,
                'span' => $spanMessage,
                'exception' => $exception,
            ]);
        } catch (Throwable) {
            // Logger failures must not break profiling either.
        }
    }

    private function normalizeLabel(string $spanMessage): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]+/', '_', strtolower($spanMessage)) ?? $spanMessage;
    }
}
