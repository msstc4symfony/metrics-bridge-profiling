<?php

declare(strict_types=1);

use Msstc4Symfony\MetricsBridgeProfiling\Collector\SpanDurationCollector;
use Msstc4Symfony\MetricsBridgeProfiling\Processor\MetricEndSpanProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->set(SpanDurationCollector::class)
        ->arg('$applicationName', '%metrics_bundle.applicationName%')
        ->arg('$componentName', '%metrics_bundle.componentName%')
    ;
    $services->set(MetricEndSpanProcessor::class);
};
