<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\ProfilingMetric;
use Msstc4Symfony\MetricsBundle\Application\Metric\LabelType;
use Msstc4Symfony\MetricsBundle\Application\Metric\MetricType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfilingMetric::class)]
final class ProfilingMetricTest extends TestCase
{
    public function testDefinitionIsStableForDashboards(): void
    {
        $metric = ProfilingMetric::SPAN_DURATION;

        self::assertSame('profiling_span_duration_histogram_seconds', $metric->value);
        self::assertSame(MetricType::HISTOGRAM, $metric->getType());
        self::assertSame('Profiling span duration in seconds', $metric->getDescription());
        self::assertSame([0.1, 0.5, 1, 3, 5, 10, 15, 30, 60, 180], $metric->getBatches());

        $labels = $metric->getLabels();
        self::assertCount(1, $labels);
        self::assertSame('message', $labels[0]->name);
        self::assertSame(LabelType::STRING, $labels[0]->type);
    }
}
