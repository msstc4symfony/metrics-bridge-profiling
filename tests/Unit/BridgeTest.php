<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use Msstc4Symfony\MetricsBridgeProfiling\Collector\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Processor\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Unit\Fixture\InMemoryRegistry;
use Msstc4Symfony\MetricsBundle\Infrastructure\Repository\MetricRepository;
use Msstc4Symfony\ProfilingBundle\Framework\Assembler\SpanAssembler;
use Msstc4Symfony\ProfilingBundle\Framework\ProfilingFactory;
use Msstc4Symfony\ProfilingBundle\Framework\Span\SpanInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Psr\Log\NullLogger;

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

    #[DataProvider('spanMessages')]
    public function testCollectorNormalisesTheLabel(string $message, string $label): void
    {
        $this->collector()->observe($message, 0.5);

        self::assertSame([[['app', 'cmp', $label], 0.5]], InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function spanMessages(): iterable
    {
        yield 'service style' => ['Order Service::process', 'order_service_process'];
        yield 'separators' => ['Foo Bar/baz', 'foo_bar_baz'];
        yield 'hyphen and underscore are kept' => ['span-1_v2', 'span-1_v2'];
        yield 'upper case' => ['REQUEST orders_LIST', 'request_orders_list'];
        yield 'unicode' => ['запрос заказов', '_'];
        yield 'spaces only' => ['   ', '_'];
        yield 'empty' => ['', ''];
    }

    public function testProcessorRecordsTheDurationFixedAtEnd(): void
    {
        $factory = new ProfilingFactory([new SpanAssembler([])], [], [new MetricEndSpanProcessor($this->collector())], new NullLogger());
        $span = $factory->createSpan('request orders_list');

        $span->end(['profiling_implicit_end' => true]);
        usleep(2000);

        self::assertSame([[['app', 'cmp', 'request_orders_list'], round($span->getDuration(), 6)]], InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    public function testProcessorRoundsTheDurationToMicroseconds(): void
    {
        $span = self::createStub(SpanInterface::class);
        $span->method('getMessage')->willReturn('request orders_list');
        $span->method('getDuration')->willReturn(0.12345649);

        new MetricEndSpanProcessor($this->collector())->process($span, []);

        self::assertSame([[['app', 'cmp', 'request_orders_list'], 0.123456]], InMemoryRegistry::sums($this->registry, self::METRIC));
    }

    private function collector(): SpanDurationCollector
    {
        return new SpanDurationCollector($this->registry, new MetricRepository([]), 'app', 'cmp');
    }
}
