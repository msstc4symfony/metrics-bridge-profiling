<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use InvalidArgumentException;
use Monolog\Attribute\WithMonologChannel;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Fixtures\RecordingMetricRecorder;
use Msstc4Symfony\MetricsBundle\Application\LogChannel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;

#[CoversClass(SpanDurationCollector::class)]
#[UsesClass(ProfilingMetric::class)]
final class SpanDurationCollectorTest extends TestCase
{
    public function testObservesTheDurationWithTheNormalizedSpanMessage(): void
    {
        $recorder = new RecordingMetricRecorder();

        (new SpanDurationCollector($recorder))->observe('Request GET /orders/{id}', 0.125);

        self::assertSame(
            [['method' => 'observe', 'metric' => ProfilingMetric::SPAN_DURATION, 'value' => 0.125, 'labels' => ['request_get_orders_id_']]],
            $recorder->calls,
        );
    }

    public function testARecorderFailureIsLoggedAndSwallowed(): void
    {
        $failure = new InvalidArgumentException('observe() needs a histogram or summary');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Could not record the span metric', [
            'metric' => 'profiling_span_duration_histogram_seconds',
            'span' => 'checkout',
            'exception' => $failure,
        ]);

        (new SpanDurationCollector(new RecordingMetricRecorder($failure), $logger))->observe('checkout', 0.1);
    }

    #[DoesNotPerformAssertions]
    public function testARecorderFailureWithoutALoggerIsSwallowed(): void
    {
        (new SpanDurationCollector(new RecordingMetricRecorder(new RuntimeException('no such metric'))))->observe('checkout', 0.1);
    }

    #[DoesNotPerformAssertions]
    public function testAFailingLoggerIsSwallowedToo(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $logger->method('error')->willThrowException(new RuntimeException('log handler down'));

        (new SpanDurationCollector(new RecordingMetricRecorder(new RuntimeException('no such metric')), $logger))->observe('checkout', 0.1);
    }

    public function testLogsToTheMetricsChannel(): void
    {
        $attribute = (new ReflectionClass(SpanDurationCollector::class))->getAttributes(WithMonologChannel::class)[0] ?? null;

        self::assertNotNull($attribute);
        self::assertSame(LogChannel::NAME, $attribute->newInstance()->channel);
    }
}
