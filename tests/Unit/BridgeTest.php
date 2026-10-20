<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Fixtures\RecordingMetricRecorder;
use Msstc4Symfony\ProfilingBundle\Application\Assembler\SpanAssembler;
use Msstc4Symfony\ProfilingBundle\Application\ProfilingFactory;
use Msstc4Symfony\ProfilingBundle\Application\Span\SpanInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(ProfilingMetric::class)]
#[CoversClass(SpanDurationCollector::class)]
#[CoversClass(MetricEndSpanProcessor::class)]
final class BridgeTest extends TestCase
{
    private RecordingMetricRecorder $recorder;

    #[Override]
    protected function setUp(): void
    {
        $this->recorder = new RecordingMetricRecorder();
    }

    #[DataProvider('spanMessages')]
    public function testCollectorNormalisesTheLabel(string $message, string $label): void
    {
        $this->collector()->observe($message, 0.5);

        self::assertSame([[[$label], 0.5]], $this->observed());
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

        self::assertSame([[['request_orders_list'], round($span->getDuration(), 6)]], $this->observed());
    }

    public function testProcessorRoundsTheDurationToMicroseconds(): void
    {
        $span = self::createStub(SpanInterface::class);
        $span->method('getMessage')->willReturn('request orders_list');
        $span->method('getDuration')->willReturn(0.12345649);

        (new MetricEndSpanProcessor($this->collector()))->process($span, []);

        self::assertSame([[['request_orders_list'], 0.123456]], $this->observed());
    }

    private function collector(): SpanDurationCollector
    {
        return new SpanDurationCollector($this->recorder);
    }

    /**
     * @return list<array{list<scalar>, float|int|null}> labels and value of every observation
     */
    private function observed(): array
    {
        return array_map(static fn (array $call): array => [$call['labels'], $call['value']], $this->recorder->calls);
    }
}
