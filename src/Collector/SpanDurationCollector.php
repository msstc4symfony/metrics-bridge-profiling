<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Collector;

use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBundle\Infrastructure\Collector\AbstractCollector;

final class SpanDurationCollector extends AbstractCollector
{
    public function observe(string $spanMessage, float $seconds): void
    {
        // Same label normalisation as metrics-bundle 1.x, so existing series continue.
        $label = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', strtolower($spanMessage)) ?? $spanMessage;

        $this->observeHistogram(ProfilingMetric::SPAN_DURATION, $seconds, [$label]);
    }
}
