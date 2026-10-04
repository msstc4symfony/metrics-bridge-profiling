<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Test\Unit;

use Msstc4Symfony\MetricsBridgeProfiling\MetricsBridgeProfilingBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetricsBridgeProfilingBundle::class)]
final class MetricsBridgeProfilingBundleTest extends TestCase
{
    public function testExtensionAliasFollowsTheFamilyNaming(): void
    {
        self::assertSame('msstc4symfony_metrics_bridge_profiling', new MetricsBridgeProfilingBundle()->getContainerExtension()?->getAlias());
    }
}
