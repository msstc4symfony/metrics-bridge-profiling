<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use Msstc4Symfony\MetricsBridgeProfiling\Collector\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Processor\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Unit\Fixture\InMemoryRegistry;
use Msstc4Symfony\MetricsBundle\Infrastructure\Collector\ProfilingCollector;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricLabelEnum;
use Msstc4Symfony\MetricsBundle\Infrastructure\Repository\MetricRepository;
use Msstc4Symfony\ProfilingBundle\Framework\Assembler\SpanAssembler;
use Msstc4Symfony\ProfilingBundle\Framework\ProfilingFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;

#[CoversClass(ProfilingMetric::class)]
#[CoversClass(SpanDurationCollector::class)]
#[CoversClass(MetricEndSpanProcessor::class)]
final class BridgeTest extends TestCase
{
    private const string METRIC = 'symfony_profiling_span_duration_histogram_seconds';

    private CollectorRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = InMemoryRegistry::create();
    }

    /**
     * Dashboards built on metrics-bundle 1.x must keep working after the take-over.
     */
    public function testMetricMatchesTheDefinitionItReplaces(): void
    {
        $legacy = MetricLabelEnum::PROFILING_SPAN_DURATION_HISTOGRAM_SECONDS;
        $bridge = ProfilingMetric::SPAN_DURATION;

        self::assertSame($legacy->value, $bridge->value);
        self::assertSame($legacy->getType(), $bridge->getType());
        self::assertSame($legacy->getDescription(), $bridge->getDescription());
        self::assertEquals($legacy->getLabels(), $bridge->getLabels());
        self::assertSame($legacy->getBatches(), $bridge->getBatches());
    }

    public function testCollectorNormalisesTheSpanMessageLikeMetricsBundle(): void
    {
        $this->collector()->observe('Order Service::process', 0.15);

        self::assertSame([[['app', 'cmp', 'order_service_process'], 0.15]], InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    /**
     * Existing series must continue: same label and value as metrics-bundle 1.x for any input.
     */
    #[DataProvider('spanMessages')]
    public function testCollectorRecordsExactlyWhatTheLegacyCollectorRecorded(string $message): void
    {
        $legacyRegistry = InMemoryRegistry::create();
        new ProfilingCollector($legacyRegistry, new MetricRepository([]), 'app', 'cmp')->setProfilingSpanDuration($message, 0.1234567);

        $this->collector()->observe($message, 0.1234567);

        self::assertSame(InMemoryRegistry::sums($legacyRegistry, self::METRIC), InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function spanMessages(): iterable
    {
        yield 'hyphen and underscore' => ['span-1_v2'];
        yield 'separators' => ['Order Service::process'];
        yield 'upper case' => ['REQUEST orders_LIST'];
        yield 'unicode' => ['запрос заказов'];
        yield 'spaces only' => ['   '];
        yield 'empty' => [''];
    }

    public function testProcessorRecordsTheDurationFixedAtEnd(): void
    {
        $factory = new ProfilingFactory([new SpanAssembler()], [], [new MetricEndSpanProcessor($this->collector())]);
        $span = $factory->createSpan('request orders_list');

        $span->end(['profiling_implicit_end' => true]);
        usleep(2000);

        self::assertSame([[['app', 'cmp', 'request_orders_list'], round($span->getDuration(), 6)]], InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    private function collector(): SpanDurationCollector
    {
        return new SpanDurationCollector($this->registry, new MetricRepository([]), 'app', 'cmp');
    }
}
