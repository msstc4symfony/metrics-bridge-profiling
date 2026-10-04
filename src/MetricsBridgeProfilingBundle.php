<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling;

use Msstc4Symfony\MetricsBridgeProfiling\DependencyInjection\Compiler\RegisterProfilingMetricsPass;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class MetricsBridgeProfilingBundle extends AbstractBundle
{
    protected string $extensionAlias = 'msstc4symfony_metrics_bridge_profiling';

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterProfilingMetricsPass());
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/Resources/config/services.php');
    }
}
