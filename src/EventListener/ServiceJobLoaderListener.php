<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\EventListener;

use Shapecode\Bundle\CronBundle\Collection\CronJobMetadataCollection;
use Shapecode\Bundle\CronBundle\CronJob\CommandRegistry;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;
use Shapecode\Bundle\CronBundle\Event\LoadJobsEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class ServiceJobLoaderListener
{
    private readonly CronJobMetadataCollection $metadataCollection;

    public function __construct(
        private readonly CommandRegistry $commandRegistry,
    ) {
        $this->metadataCollection = new CronJobMetadataCollection();
    }

    public function __invoke(LoadJobsEvent $event): void
    {
        foreach ($this->metadataCollection as $job) {
            $event->addJob($job);
        }
    }

    /**
     * @param list<string> $tags
     * @param list<class-string> $dependsOn
     */
    public function addCommand(
        string $expression,
        Command $command,
        ?string $arguments = null,
        int $maxInstances = 1,
        array $tags = [],
        array $dependsOn = [],
        DependencyMode $dependencyMode = DependencyMode::AND,
        DependencyFailureMode $onDependencyFailure = DependencyFailureMode::SKIP,
    ): void {
        // Register the command in the registry for class name resolution
        $this->commandRegistry->register($command);

        $this->metadataCollection->add(
            CronJobMetadata::createByCommand(
                $expression,
                $command,
                $arguments,
                $maxInstances,
                $tags,
                $dependsOn,
                $dependencyMode,
                $onDependencyFailure,
            ),
        );
    }
}
