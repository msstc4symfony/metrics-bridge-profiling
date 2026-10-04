<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Collector;

use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBundle\Infrastructure\Collector\AbstractCollector;

final class SpanDurationCollector extends AbstractCollector
{
    public function observe(string $spanMessage, float $seconds): void
    {
        $this->observeHistogram(ProfilingMetric::SPAN_DURATION, $seconds, [$this->normalizeLabel($spanMessage)]);
    }

    private function normalizeLabel(string $spanMessage): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]+/', '_', strtolower($spanMessage)) ?? $spanMessage;
    }
}
