<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\CronJob;

use Symfony\Component\Console\Command\Command;

final class CommandRegistry
{
    /** @var array<class-string, string> Maps PHP class name to command name */
    private array $classToCommand = [];

    public function register(Command $command): void
    {
        $className = $command::class;
        $commandName = $command->getName();

        if ($commandName !== null) {
            $this->classToCommand[$className] = $commandName;
        }
    }

    /**
     * Resolve a PHP class name to its command name.
     *
     * @param class-string $className
     */
    public function resolveClassName(string $className): ?string
    {
        return $this->classToCommand[$className] ?? null;
    }

    /**
     * Resolve multiple class names to command names.
     *
     * @param list<class-string> $classNames
     *
     * @return list<string> Array of command names
     */
    public function resolveClassNames(array $classNames): array
    {
        $resolved = [];
        foreach ($classNames as $className) {
            $commandName = $this->resolveClassName($className);
            if ($commandName !== null) {
                $resolved[] = $commandName;
            }
        }

        return $resolved;
    }

    public function clear(): void
    {
        $this->classToCommand = [];
    }
}
