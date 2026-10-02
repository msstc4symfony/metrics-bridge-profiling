<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Integration;

use Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler\TakeOverProfilingMetricsPass;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBridgeProfiling\Processor\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Integration\Kernel\TestKernel;
use Msstc4Symfony\MetricsBridgeProfiling\Test\Unit\Fixture\InMemoryRegistry;
use Msstc4Symfony\ProfilingBundle\Framework\ProfilingFactoryInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

#[CoversNothing]
final class KernelBridgeTest extends TestCase
{
    private TestKernel $kernel;

    /** @var list<string> */
    private array $metricsDeprecations = [];

    /** @var (callable(Throwable): void)|null */
    private $exceptionHandler;

    protected function setUp(): void
    {
        new Filesystem()->remove(TestKernel::cacheRoot());
        // trigger_deprecation() silences its notice with @, which PHPUnit ignores; record it directly.
        set_error_handler(function (int $level, string $message): bool {
            if (str_contains($message, 'msstc4symfony/metrics-bridge-profiling')) {
                $this->metricsDeprecations[] = $message;
            }

            return false;
        }, E_USER_DEPRECATED);
        $this->exceptionHandler = $this->currentExceptionHandler();
        $this->kernel = new TestKernel('test', false);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        $this->kernel->shutdown();
        // With another error handler active, FrameworkBundle::boot() on symfony/error-handler < 6.4.44
        // leaves its exception handler installed, which PHPUnit reports as risky.
        while (null !== ($handler = $this->currentExceptionHandler()) && $handler !== $this->exceptionHandler) {
            restore_exception_handler();
        }

        new Filesystem()->remove(TestKernel::cacheRoot());
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
        self::assertSame([], $this->metricsDeprecations, 'The deprecated metrics-bundle processor must not be instantiated.');
    }

    public function testMetricsBundleKnowsTheMetricAndItsOwnProcessorGetsNoSpans(): void
    {
        $enums = $this->kernel->getContainer()->getParameter(TakeOverProfilingMetricsPass::METRIC_ENUMS_PARAMETER);
        self::assertIsArray($enums);
        self::assertContains(ProfilingMetric::class, $enums);

        $factory = $this->container()->get(ProfilingFactoryInterface::class);
        self::assertIsObject($factory);
        $processors = new ReflectionProperty($factory, 'endSpanProcessors')->getValue($factory);
        self::assertIsIterable($processors);
        $classes = [];
        foreach ($processors as $processor) {
            self::assertIsObject($processor);
            $classes[] = $processor::class;
        }

        self::assertContains(MetricEndSpanProcessor::class, $classes);
        self::assertNotContains(TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR, $classes);
    }

    private function currentExceptionHandler(): ?callable
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
