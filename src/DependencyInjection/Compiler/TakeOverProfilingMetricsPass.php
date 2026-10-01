<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler;

use LogicException;
use Msstc4Symfony\MetricsBridgeProfiling\Enum\ProfilingMetric;
use Msstc4Symfony\ProfilingBundle\Framework\Processor\EndSpan\EndSpanProcessorInterface;
use Override;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the bridge's metric with metrics-bundle and stops metrics-bundle's own
 * deprecated span processor from receiving spans, which would record each span twice.
 *
 * Runs after autoconfiguration has turned the processor interface into a tag and before
 * profiling's tagged iterator is resolved; the tag is cleared rather than the service
 * removed, so application references to the old processor stay valid.
 */
final class TakeOverProfilingMetricsPass implements CompilerPassInterface
{
    /** @internal */
    public const string METRIC_ENUMS_PARAMETER = 'metrics_bundle.metric_enums';

    /** @internal */
    public const string APPLICATION_NAME_PARAMETER = 'metrics_bundle.applicationName';

    /** @internal */
    public const string DEPRECATED_PROCESSOR = 'Msstc4Symfony\MetricsBundle\Framework\Profiling\Processor\EndSpan\MetricProcessor';

    #[Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter(self::APPLICATION_NAME_PARAMETER)) {
            throw new LogicException('MetricsBridgeProfilingBundle needs MetricsBundle: register Msstc4Symfony\MetricsBundle\MetricsBundle in config/bundles.php.');
        }

        $container->setParameter(self::METRIC_ENUMS_PARAMETER, $this->metricEnums($container));

        $parameters = $container->getParameterBag();
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($parameters->resolveValue($definition->getClass() ?? $id) === self::DEPRECATED_PROCESSOR) {
                $definition->clearTag(EndSpanProcessorInterface::class);
            }
        }
    }

    /**
     * @return list<mixed>
     */
    private function metricEnums(ContainerBuilder $container): array
    {
        if (!$container->hasParameter(self::METRIC_ENUMS_PARAMETER)) {
            return [ProfilingMetric::class];
        }

        $enums = $container->getParameterBag()->resolveValue($container->getParameter(self::METRIC_ENUMS_PARAMETER));
        if (!is_array($enums)) {
            throw new LogicException(sprintf('Parameter "%s" must be a list of enum classes known at compile time, got %s.', self::METRIC_ENUMS_PARAMETER, get_debug_type($enums)));
        }

        $enums = array_values($enums);
        if (!in_array(ProfilingMetric::class, $enums, true)) {
            $enums[] = ProfilingMetric::class;
        }

        return $enums;
    }
}
