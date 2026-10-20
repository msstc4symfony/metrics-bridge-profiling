<?php

declare(strict_types=1);

namespace Msstc4Symfony\MetricsBridgeProfiling\Infrastructure\Metrics;

use Msstc4Symfony\ProfilingBundle\Application\Processor\EndSpan\EndSpanProcessorInterface;
use Msstc4Symfony\ProfilingBundle\Application\Span\SpanInterface;
use Override;

/**
 * Records the duration fixed by SpanInterface::end(), rounded to microseconds;
 * the end context (for example profiling_implicit_end) never becomes a label.
 *
 * @internal
 */
final readonly class MetricEndSpanProcessor implements EndSpanProcessorInterface
{
    public function __construct(
        private SpanDurationCollector $collector,
    ) {
    }

    #[Override]
    public function process(SpanInterface $span, array $context): void
    {
        $this->collector->observe($span->getMessage(), round($span->getDuration(), 6));
    }
}
