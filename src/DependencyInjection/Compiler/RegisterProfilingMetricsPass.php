<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler;

use LogicException;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterProfilingMetricsPass implements CompilerPassInterface
{
    private const string METRIC_ENUMS_PARAMETER = 'msstc4symfony_metrics.metric_enums';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(self::METRIC_ENUMS_PARAMETER)) {
            throw new LogicException('MetricsBridgeProfilingBundle needs MetricsBundle: register Msstc4Symfony\MetricsBundle\MetricsBundle in config/bundles.php.');
        }

        $enums = $container->getParameter(self::METRIC_ENUMS_PARAMETER);
        if (!is_array($enums)) {
            throw new LogicException(sprintf('Parameter "%s" must be a list of enum classes, got %s.', self::METRIC_ENUMS_PARAMETER, get_debug_type($enums)));
        }

        if (!in_array(ProfilingMetric::class, $enums, true)) {
            $enums[] = ProfilingMetric::class;
        }

        $container->setParameter(self::METRIC_ENUMS_PARAMETER, $enums);
    }
}
