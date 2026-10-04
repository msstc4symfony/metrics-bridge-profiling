<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use LogicException;
use Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler\RegisterProfilingMetricsPass;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\MetricsBundle\Infrastructure\Enum\MetricLabelEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(RegisterProfilingMetricsPass::class)]
final class RegisterProfilingMetricsPassTest extends TestCase
{
    private const string PARAMETER = 'msstc4symfony_metrics.metric_enums';

    public function testAppendsTheMetricAfterTheExistingEnums(): void
    {
        $container = $this->container([MetricLabelEnum::class, 'App\Metrics']);

        new RegisterProfilingMetricsPass()->process($container);

        self::assertSame([MetricLabelEnum::class, 'App\Metrics', ProfilingMetric::class], $container->getParameter(self::PARAMETER));
    }

    public function testAppendsTheMetricOnlyOnce(): void
    {
        $container = $this->container([MetricLabelEnum::class]);

        new RegisterProfilingMetricsPass()->process($container);
        new RegisterProfilingMetricsPass()->process($container);

        self::assertSame([MetricLabelEnum::class, ProfilingMetric::class], $container->getParameter(self::PARAMETER));
    }

    public function testExplainsAMissingMetricsBundle(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('register Msstc4Symfony\MetricsBundle\MetricsBundle');

        new RegisterProfilingMetricsPass()->process(new ContainerBuilder());
    }

    public function testRefusesAParameterThatIsNotAList(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter(self::PARAMETER, 'App\Metrics');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must be a list of enum classes');

        new RegisterProfilingMetricsPass()->process($container);
    }

    /**
     * @param list<string> $enums
     */
    private function container(array $enums): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter(self::PARAMETER, $enums);

        return $container;
    }
}
