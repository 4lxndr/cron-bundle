<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Domain;

use RuntimeException;
use Symfony\Component\Console\Command\Command;

use function str_replace;

final class CronJobMetadata
{
    /**
     * @param list<string> $tags
     * @param list<class-string> $dependsOnClasses
     */
    private function __construct(
        public readonly string $expression,
        public readonly string $command,
        public readonly ?string $arguments = null,
        public readonly int $maxInstances = 1,
        public readonly ?string $description = null,
        public readonly array $tags = [],
        public readonly array $dependsOnClasses = [],
        public readonly DependencyMode $dependencyMode = DependencyMode::AND,
        public readonly DependencyFailureMode $onDependencyFailure = DependencyFailureMode::SKIP,
    ) {
    }

    /**
     * @param list<string> $tags
     * @param list<class-string> $dependsOnClasses
     */
    public static function createByCommand(
        string $expression,
        Command $command,
        ?string $arguments = null,
        int $maxInstances = 1,
        array $tags = [],
        array $dependsOnClasses = [],
        DependencyMode $dependencyMode = DependencyMode::AND,
        DependencyFailureMode $onDependencyFailure = DependencyFailureMode::SKIP,
    ): self {
        $commandName = $command->getName();

        if ($commandName === null) {
            throw new RuntimeException('command has to have a name provided', 1653426725688);
        }

        return new self(
            str_replace('\\', '', $expression),
            $commandName,
            $arguments,
            $maxInstances,
            $command->getDescription(),
            $tags,
            $dependsOnClasses,
            $dependencyMode,
            $onDependencyFailure,
        );
    }
}
