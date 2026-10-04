<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Enum;

use Msstc4Symfony\MetricsBundle\Infrastructure\Entity\Label;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricLabelEnumInterface;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricLabelTypeEnum;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricTypeEnum;
use Override;

enum ProfilingMetric: string implements MetricLabelEnumInterface
{
    case SPAN_DURATION = 'profiling_span_duration_histogram_seconds';

    #[Override]
    public function getType(): MetricTypeEnum
    {
        return MetricTypeEnum::HISTOGRAM;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Profiling span duration in seconds';
    }

    #[Override]
    public function getLabels(): array
    {
        return [new Label('message', MetricLabelTypeEnum::STRING, 'Profiling span message')];
    }

    #[Override]
    public function getBatches(): array
    {
        return [0.1, 0.5, 1, 3, 5, 10, 15, 30, 60, 180];
    }
}
