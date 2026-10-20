<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Integration;

use Monolog\Logger;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Fixtures\ExceptionHandlerSnapshot;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Fixtures\InMemoryRegistry;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Integration\Kernel\TestKernel;
use Msstc4Symfony\MetricsBundle\Application\LogChannel;
use Msstc4Symfony\ProfilingBundle\Application\ProfilingFactoryInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

#[CoversNothing]
final class KernelBridgeTest extends TestCase
{
    private TestKernel $kernel;

    /** @var list<string> */
    private array $bundleDeprecations = [];

    private ExceptionHandlerSnapshot $exceptionHandlers;

    #[Override]
    protected function setUp(): void
    {
        (new Filesystem())->remove(TestKernel::cacheRoot());
        // trigger_deprecation() silences its notice with @, which PHPUnit ignores; record it directly.
        set_error_handler(function (int $level, string $message): bool {
            if (str_contains($message, 'msstc4symfony/')) {
                $this->bundleDeprecations[] = $message;
            }

            return false;
        }, E_USER_DEPRECATED);
        $this->exceptionHandlers = ExceptionHandlerSnapshot::take();
        $this->kernel = new TestKernel('test', false);
        $this->kernel->boot();
    }

    #[Override]
    protected function tearDown(): void
    {
        restore_error_handler();
        $this->kernel->shutdown();
        // FrameworkBundle::boot() on symfony/error-handler before 7.4.17 leaves its exception handler
        // installed when another error handler is active, which PHPUnit reports as risky.
        $this->exceptionHandlers->restore();

        (new Filesystem())->remove(TestKernel::cacheRoot());
    }

    public function testASpanIsRecordedOnceWithTheApplicationLabels(): void
    {
        $factory = $this->container()->get(ProfilingFactoryInterface::class);
        self::assertInstanceOf(ProfilingFactoryInterface::class, $factory);
        $registry = $this->container()->get(RegistryInterface::class);
        self::assertInstanceOf(CollectorRegistry::class, $registry);

        $span = $factory->createSpan('import orders');
        $span->end();

        self::assertSame(
            [[['shop', 'api', 'import_orders'], round($span->getDuration(), 6)]],
            InMemoryRegistry::sums($registry, 'symfony_' . ProfilingMetric::SPAN_DURATION->value),
        );
        self::assertSame([], $this->bundleDeprecations, 'No msstc4symfony bundle may trigger a deprecation while the kernel boots and records a span.');
    }

    public function testMetricsBundleKnowsTheMetric(): void
    {
        $enums = $this->kernel->getContainer()->getParameter('msstc4symfony_metrics.metric_enums');
        self::assertIsArray($enums);
        self::assertContains(ProfilingMetric::class, $enums);
    }

    public function testExactlyOneBridgeProcessorIsRegistered(): void
    {
        $factory = $this->container()->get(ProfilingFactoryInterface::class);
        self::assertIsObject($factory);
        $processors = (new ReflectionProperty($factory, 'endSpanProcessors'))->getValue($factory);
        self::assertIsIterable($processors);
        $classes = [];
        foreach ($processors as $processor) {
            self::assertIsObject($processor);
            $classes[] = $processor::class;
        }

        self::assertSame(1, count(array_keys($classes, MetricEndSpanProcessor::class, true)));
    }

    public function testTheCollectorLogsToTheUndecoratedMetricsChannel(): void
    {
        // Reaches the private collector through the factory: a monolog-bundle change could drop the channel silently.
        $factory = $this->container()->get(ProfilingFactoryInterface::class);
        self::assertIsObject($factory);
        $processors = (new ReflectionProperty($factory, 'endSpanProcessors'))->getValue($factory);
        self::assertIsIterable($processors);
        $processor = null;
        foreach ($processors as $candidate) {
            if ($candidate instanceof MetricEndSpanProcessor) {
                $processor = $candidate;
            }
        }

        self::assertInstanceOf(MetricEndSpanProcessor::class, $processor);
        $collector = (new ReflectionProperty($processor, 'collector'))->getValue($processor);
        self::assertInstanceOf(SpanDurationCollector::class, $collector);
        $logger = (new ReflectionProperty($collector, 'logger'))->getValue($collector);
        self::assertInstanceOf(Logger::class, $logger);
        self::assertSame(LogChannel::NAME, $logger->getName());
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
