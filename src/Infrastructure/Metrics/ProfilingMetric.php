<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics;

use Msstc4Symfony\MetricsBundle\Application\Metric\Label;
use Msstc4Symfony\MetricsBundle\Application\Metric\LabelType;
use Msstc4Symfony\MetricsBundle\Application\Metric\MetricDefinitionInterface;
use Msstc4Symfony\MetricsBundle\Application\Metric\MetricType;
use Override;

enum ProfilingMetric: string implements MetricDefinitionInterface
{
    case SPAN_DURATION = 'profiling_span_duration_histogram_seconds';

    #[Override]
    public function getType(): MetricType
    {
        return MetricType::HISTOGRAM;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Profiling span duration in seconds';
    }

    #[Override]
    public function getLabels(): array
    {
        return [new Label('message', LabelType::STRING, 'Profiling span message')];
    }

    #[Override]
    public function getBatches(): array
    {
        return [0.1, 0.5, 1, 3, 5, 10, 15, 30, 60, 180];
    }
}
