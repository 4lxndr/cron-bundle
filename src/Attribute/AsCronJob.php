<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Attribute;

use Attribute;
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class AsCronJob
{
    /**
     * @param list<string> $tags
     * @param list<class-string> $dependsOn
     * @param list<array{string, string}> $pauseWindows e.g. [['13:00', '15:00'], ['22:00', '23:00']]
     */
    public function __construct(
        public readonly string $schedule,
        public readonly ?string $arguments = null,
        public readonly int $maxInstances = 1,
        public readonly array $tags = [],
        public readonly array $dependsOn = [],
        public readonly DependencyMode $dependencyMode = DependencyMode::AND,
        public readonly DependencyFailureMode $onDependencyFailure = DependencyFailureMode::SKIP,
        public readonly array $pauseWindows = [],
    ) {
    }
}
