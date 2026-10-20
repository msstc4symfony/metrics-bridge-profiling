<?php

declare(strict_types=1);

use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\MetricEndSpanProcessor;
use Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics\SpanDurationCollector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->set(SpanDurationCollector::class);
    $services->set(MetricEndSpanProcessor::class);
};
