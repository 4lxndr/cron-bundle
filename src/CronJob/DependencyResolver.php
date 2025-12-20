<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\CronJob;

use Shapecode\Bundle\CronBundle\Domain\DependencyMode;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepository;

use function implode;
use function in_array;
use function sprintf;

final class DependencyResolver
{
    public function __construct(
        private readonly CronJobResultRepository $resultRepository,
    ) {
    }

    /**
     * Check if a job can run based on its dependencies.
     *
     * @return array{canRun: bool, reason: string|null}
     */
    public function canJobRun(CronJob $job): array
    {
        if ($job->dependencies->isEmpty()) {
            return ['canRun' => true, 'reason' => null];
        }

        $dependencyStatuses = [];
        foreach ($job->dependencies as $dependency) {
            $dependencyStatuses[] = $this->isDependencySatisfied($dependency);
        }

        $dependencyMode = $job->dependencyMode ?? DependencyMode::AND;

        $satisfied = match ($dependencyMode) {
            DependencyMode::AND => !in_array(false, $dependencyStatuses, true),
            DependencyMode::OR => in_array(true, $dependencyStatuses, true),
        };

        if ($satisfied) {
            return ['canRun' => true, 'reason' => null];
        }

        $reason = sprintf(
            'Dependencies not satisfied (mode: %s)',
            $dependencyMode->value,
        );

        return ['canRun' => false, 'reason' => $reason];
    }

    /**
     * Check if a dependency has succeeded in its last run.
     */
    private function isDependencySatisfied(CronJob $dependency): bool
    {
        // Get the most recent result for this job
        $lastResult = $this->resultRepository->findLatestByJob($dependency);

        if ($lastResult === null) {
            // Never run = not satisfied
            return false;
        }

        // Check if it succeeded (exit code 0)
        return $lastResult->statusCode === 0;
    }

    /**
     * Detect circular dependencies in the entire job graph.
     *
     * @param list<CronJob> $jobs
     *
     * @return list<string> List of circular dependency descriptions
     */
    public function detectCircularDependencies(array $jobs): array
    {
        $circles = [];
        $visited = [];
        $recursionStack = [];

        foreach ($jobs as $job) {
            $jobId = $job->getId();
            if ($jobId !== null && !isset($visited[$jobId])) {
                $path = $this->detectCircularDependenciesRecursive(
                    $job,
                    $visited,
                    $recursionStack,
                    [],
                );
                if ($path !== null) {
                    $circles[] = implode(' -> ', $path);
                }
            }
        }

        return $circles;
    }

    /**
     * @param array<int, bool> $visited
     * @param array<int, bool> $recursionStack
     * @param list<string> $currentPath
     *
     * @return list<string>|null
     */
    private function detectCircularDependenciesRecursive(
        CronJob $job,
        array &$visited,
        array &$recursionStack,
        array $currentPath,
    ): ?array {
        $jobId = $job->getId();
        if ($jobId === null) {
            return null;
        }

        $visited[$jobId] = true;
        $recursionStack[$jobId] = true;
        $currentPath[] = $job->command;

        foreach ($job->dependencies as $dependency) {
            $depId = $dependency->getId();
            if ($depId === null) {
                continue;
            }

            if (!isset($visited[$depId])) {
                $result = $this->detectCircularDependenciesRecursive(
                    $dependency,
                    $visited,
                    $recursionStack,
                    $currentPath,
                );
                if ($result !== null) {
                    return $result;
                }
            } elseif (isset($recursionStack[$depId])) {
                // Found a cycle
                $currentPath[] = $dependency->command;

                return $currentPath;
            }
        }

        $recursionStack[$jobId] = false;

        return null;
    }

    /**
     * Get all jobs that depend on a given job (reverse dependencies).
     *
     * @param list<CronJob> $allJobs
     *
     * @return list<CronJob>
     */
    public function getReverseDependencies(CronJob $job, array $allJobs): array
    {
        $reverse = [];
        foreach ($allJobs as $otherJob) {
            if ($otherJob->dependencies->contains($job)) {
                $reverse[] = $otherJob;
            }
        }

        return $reverse;
    }
}
