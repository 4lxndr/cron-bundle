<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Collection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Collection\CronJobRunningCollection;
use Shapecode\Bundle\CronBundle\Domain\CronJobRunning;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Symfony\Component\Process\Process;

#[CoversClass(CronJobRunningCollection::class)]
class CronJobRunningCollectionTest extends TestCase
{
    public function testConstructorWithVariadicArguments(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');

        $process1 = new Process(['echo', 'test1']);
        $process2 = new Process(['echo', 'test2']);

        $running1 = new CronJobRunning($job1, $process1);
        $running2 = new CronJobRunning($job2, $process2);

        $collection = new CronJobRunningCollection($running1, $running2);

        self::assertCount(2, $collection);
        self::assertTrue($collection->contains($running1));
        self::assertTrue($collection->contains($running2));
    }

    public function testConstructorWithNoArguments(): void
    {
        $collection = new CronJobRunningCollection();

        self::assertCount(0, $collection);
        self::assertTrue($collection->isEmpty());
    }

    public function testInheritsArrayCollectionMethods(): void
    {
        $job = new CronJob('command1', '@daily');
        $process = new Process(['echo', 'test']);
        $running1 = new CronJobRunning($job, $process);

        $collection = new CronJobRunningCollection($running1);

        // Test add method from ArrayCollection
        $running2 = new CronJobRunning($job, $process);
        $collection->add($running2);

        self::assertCount(2, $collection);
        self::assertTrue($collection->contains($running2));

        // Test removeElement method from ArrayCollection
        $collection->removeElement($running1);

        self::assertCount(1, $collection);
        self::assertFalse($collection->contains($running1));
    }

    public function testIterableInterface(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');

        $process1 = new Process(['echo', 'test1']);
        $process2 = new Process(['echo', 'test2']);

        $running1 = new CronJobRunning($job1, $process1);
        $running2 = new CronJobRunning($job2, $process2);

        $collection = new CronJobRunningCollection($running1, $running2);

        $runnings = [];
        foreach ($collection as $running) {
            $runnings[] = $running;
        }

        self::assertCount(2, $runnings);
        self::assertContains($running1, $runnings);
        self::assertContains($running2, $runnings);
    }
}
