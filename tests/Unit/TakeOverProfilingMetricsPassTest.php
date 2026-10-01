<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use LogicException;
use Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler\TakeOverProfilingMetricsPass;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricLabelEnum;
use Msstc4Symfony\ProfilingBundle\Framework\Processor\EndSpan\EndSpanProcessorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

#[CoversClass(TakeOverProfilingMetricsPass::class)]
final class TakeOverProfilingMetricsPassTest extends TestCase
{
    public function testAppendsTheMetricOnce(): void
    {
        $container = $this->container();
        $container->setParameter('app.metric_enums', [MetricLabelEnum::class, 'App\Metrics']);
        $container->setParameter(TakeOverProfilingMetricsPass::METRIC_ENUMS_PARAMETER, '%app.metric_enums%');

        new TakeOverProfilingMetricsPass()->process($container);
        new TakeOverProfilingMetricsPass()->process($container);

        self::assertSame(
            [MetricLabelEnum::class, 'App\Metrics', ProfilingMetric::class],
            $container->getParameter(TakeOverProfilingMetricsPass::METRIC_ENUMS_PARAMETER),
        );
    }

    public function testDeprecatedProcessorStopsReceivingSpansUnderAnyIdButStaysReferenceable(): void
    {
        $container = $this->container();
        $container->register(TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR)->addTag(EndSpanProcessorInterface::class);
        $container->register('app.legacy_processor', TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR)->addTag(EndSpanProcessorInterface::class);
        $container->setParameter('app.processor_class', TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR);
        $container->register('app.parameterised_processor', '%app.processor_class%')->addTag(EndSpanProcessorInterface::class);
        $container->register('app.other_processor', 'App\OtherProcessor')->addTag(EndSpanProcessorInterface::class);
        $container->register('app.wrapper', 'App\Wrapper')->addArgument(new Reference(TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR));

        new TakeOverProfilingMetricsPass()->process($container);

        self::assertSame(['app.other_processor'], array_keys($container->findTaggedServiceIds(EndSpanProcessorInterface::class)));
        self::assertTrue($container->hasDefinition(TakeOverProfilingMetricsPass::DEPRECATED_PROCESSOR));
    }

    public function testWithoutTheParameterTheMetricIsTheOnlyEnum(): void
    {
        $container = $this->container();

        new TakeOverProfilingMetricsPass()->process($container);

        self::assertSame([ProfilingMetric::class], $container->getParameter(TakeOverProfilingMetricsPass::METRIC_ENUMS_PARAMETER));
    }

    public function testRefusesToOverwriteAParameterUnknownAtCompileTime(): void
    {
        $container = $this->container();
        $container->setParameter(TakeOverProfilingMetricsPass::METRIC_ENUMS_PARAMETER, 'App\Metrics');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must be a list of enum classes known at compile time');

        new TakeOverProfilingMetricsPass()->process($container);
    }

    public function testExplainsAMissingMetricsBundle(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('register Msstc4Symfony\MetricsBundle\MetricsBundle');

        new TakeOverProfilingMetricsPass()->process(new ContainerBuilder());
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter(TakeOverProfilingMetricsPass::APPLICATION_NAME_PARAMETER, 'app');

        return $container;
    }
}
