<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\CronJob;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\CronJob\DependencyResolver;
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Entity\CronJobResult;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepository;

#[CoversClass(DependencyResolver::class)]
final class DependencyResolverTest extends TestCase
{
    public function testCanJobRunWithNoDependencies(): void
    {
        $resultRepo = self::createStub(CronJobResultRepository::class);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');

        $result = $resolver->canJobRun($job);

        self::assertTrue($result['canRun']);
        self::assertNull($result['reason']);
    }

    public function testCanJobRunWithSatisfiedDependencyAndMode(): void
    {
        $dependency = new CronJob('dependency-job', '@daily');

        $successResult = new CronJobResult(
            $dependency,
            0.5,
            0,
            'Success',
            new DateTimeImmutable(),
        );

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->with($dependency)
            ->willReturn($successResult);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        $job->dependencyMode = DependencyMode::AND;
        $job->addDependency($dependency);

        $result = $resolver->canJobRun($job);

        self::assertTrue($result['canRun']);
        self::assertNull($result['reason']);
    }

    public function testCanJobRunWithFailedDependencyAndMode(): void
    {
        $dependency = new CronJob('dependency-job', '@daily');

        $failedResult = new CronJobResult(
            $dependency,
            0.5,
            1,
            'Failed',
            new DateTimeImmutable(),
        );

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->with($dependency)
            ->willReturn($failedResult);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        $job->dependencyMode = DependencyMode::AND;
        $job->addDependency($dependency);

        $result = $resolver->canJobRun($job);

        self::assertFalse($result['canRun']);
        self::assertIsString($result['reason']);
        self::assertStringContainsString('Dependencies not satisfied', $result['reason']);
    }

    public function testCanJobRunWithOrModeAndOneSatisfied(): void
    {
        $dependency1 = new CronJob('dependency1', '@daily');
        $dependency2 = new CronJob('dependency2', '@daily');

        $successResult = new CronJobResult(
            $dependency1,
            0.5,
            0,
            'Success',
            new DateTimeImmutable(),
        );

        $failedResult = new CronJobResult(
            $dependency2,
            0.5,
            1,
            'Failed',
            new DateTimeImmutable(),
        );

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->willReturnMap([
                [$dependency1, $successResult],
                [$dependency2, $failedResult],
            ]);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        $job->dependencyMode = DependencyMode::OR;
        $job->addDependency($dependency1);
        $job->addDependency($dependency2);

        $result = $resolver->canJobRun($job);

        self::assertTrue($result['canRun']);
        self::assertNull($result['reason']);
    }

    public function testCanJobRunWithNeverRunDependency(): void
    {
        $dependency = new CronJob('dependency-job', '@daily');

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->with($dependency)
            ->willReturn(null);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        $job->dependencyMode = DependencyMode::AND;
        $job->addDependency($dependency);

        $result = $resolver->canJobRun($job);

        self::assertFalse($result['canRun']);
        self::assertIsString($result['reason']);
        self::assertStringContainsString('Dependencies not satisfied', $result['reason']);
    }

    public function testCanJobRunWithNullDependencyModeDefaultsToAnd(): void
    {
        $dependency = new CronJob('dependency-job', '@daily');

        $successResult = new CronJobResult(
            $dependency,
            0.5,
            0,
            'Success',
            new DateTimeImmutable(),
        );

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->with($dependency)
            ->willReturn($successResult);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        // dependencyMode is null by default now
        self::assertNull($job->dependencyMode);
        $job->addDependency($dependency);

        $result = $resolver->canJobRun($job);

        self::assertTrue($result['canRun']);
        self::assertNull($result['reason']);
    }

    public function testCanJobRunWithNullDependencyModeFailsLikeAnd(): void
    {
        $dependency = new CronJob('dependency-job', '@daily');

        $failedResult = new CronJobResult(
            $dependency,
            0.5,
            1,
            'Failed',
            new DateTimeImmutable(),
        );

        $resultRepo = self::createStub(CronJobResultRepository::class);
        $resultRepo->method('findLatestByJob')
            ->with($dependency)
            ->willReturn($failedResult);

        $resolver = new DependencyResolver($resultRepo);

        $job = new CronJob('test-job', '@daily');
        // dependencyMode is null by default now
        self::assertNull($job->dependencyMode);
        $job->addDependency($dependency);

        $result = $resolver->canJobRun($job);

        self::assertFalse($result['canRun']);
        self::assertIsString($result['reason']);
        self::assertStringContainsString('Dependencies not satisfied', $result['reason']);
    }

    public function testDetectCircularDependenciesSimple(): void
    {
        $resultRepo = self::createStub(CronJobResultRepository::class);

        $resolver = new DependencyResolver($resultRepo);

        // Create jobs with reflection to set IDs
        $job1 = new CronJob('job1', '@daily');
        $job2 = new CronJob('job2', '@daily');

        // Use reflection to set IDs (since they're normally set by Doctrine)
        $reflection1 = new \ReflectionClass($job1);
        $idProperty1 = $reflection1->getProperty('id');
        $idProperty1->setValue($job1, 1);

        $reflection2 = new \ReflectionClass($job2);
        $idProperty2 = $reflection2->getProperty('id');
        $idProperty2->setValue($job2, 2);

        // job1 depends on job2, job2 depends on job1 (circular)
        $job1->addDependency($job2);
        $job2->addDependency($job1);

        $circles = $resolver->detectCircularDependencies([$job1, $job2]);

        self::assertNotEmpty($circles);
        self::assertStringContainsString('job1', $circles[0]);
        self::assertStringContainsString('job2', $circles[0]);
    }

    public function testDetectNoCircularDependencies(): void
    {
        $resultRepo = self::createStub(CronJobResultRepository::class);

        $resolver = new DependencyResolver($resultRepo);

        $job1 = new CronJob('job1', '@daily');
        $job2 = new CronJob('job2', '@daily');

        // Use reflection to set IDs
        $reflection1 = new \ReflectionClass($job1);
        $idProperty1 = $reflection1->getProperty('id');
        $idProperty1->setValue($job1, 1);

        $reflection2 = new \ReflectionClass($job2);
        $idProperty2 = $reflection2->getProperty('id');
        $idProperty2->setValue($job2, 2);

        // No dependencies - no circular dependencies possible
        $circles = $resolver->detectCircularDependencies([$job1, $job2]);

        self::assertEmpty($circles);
    }
}
