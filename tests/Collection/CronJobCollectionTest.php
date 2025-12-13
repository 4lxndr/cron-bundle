<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Collection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Collection\CronJobCollection;
use Shapecode\Bundle\CronBundle\Entity\CronJob;

#[CoversClass(CronJobCollection::class)]
class CronJobCollectionTest extends TestCase
{
    public function testConstructorWithVariadicArguments(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');
        $job3 = new CronJob('command3', '@weekly');

        $collection = new CronJobCollection($job1, $job2, $job3);

        self::assertCount(3, $collection);
        self::assertTrue($collection->contains($job1));
        self::assertTrue($collection->contains($job2));
        self::assertTrue($collection->contains($job3));
    }

    public function testConstructorWithNoArguments(): void
    {
        $collection = new CronJobCollection();

        self::assertCount(0, $collection);
        self::assertTrue($collection->isEmpty());
    }

    public function testMapToCommand(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');
        $job3 = new CronJob('command3', '@weekly');

        $collection = new CronJobCollection($job1, $job2, $job3);

        $commands = $collection->mapToCommand();

        self::assertCount(3, $commands);
        self::assertSame(['command1', 'command2', 'command3'], $commands);
    }

    public function testMapToCommandWithEmptyCollection(): void
    {
        $collection = new CronJobCollection();

        $commands = $collection->mapToCommand();

        self::assertCount(0, $commands);
        self::assertEmpty($commands);
    }

    public function testInheritsArrayCollectionMethods(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');

        $collection = new CronJobCollection($job1, $job2);

        // Test add method from ArrayCollection
        $job3 = new CronJob('command3', '@weekly');
        $collection->add($job3);

        self::assertCount(3, $collection);
        self::assertTrue($collection->contains($job3));

        // Test removeElement method from ArrayCollection
        $collection->removeElement($job2);

        self::assertCount(2, $collection);
        self::assertFalse($collection->contains($job2));
    }

    public function testIterableInterface(): void
    {
        $job1 = new CronJob('command1', '@daily');
        $job2 = new CronJob('command2', '@hourly');

        $collection = new CronJobCollection($job1, $job2);

        $jobs = [];
        foreach ($collection as $job) {
            $jobs[] = $job;
        }

        self::assertCount(2, $jobs);
        self::assertContains($job1, $jobs);
        self::assertContains($job2, $jobs);
    }
}
