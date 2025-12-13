<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Entity\CronJob;

#[CoversClass(CronJob::class)]
class CronJobTest extends TestCase
{
    public function testCreation(): void
    {
        $job = new CronJob('test-command', '@daily');

        self::assertSame('test-command', $job->command);
        self::assertSame('@daily', $job->period);
        self::assertNull($job->arguments);
        self::assertNull($job->description);
        self::assertSame(0, $job->runningInstances);
        self::assertSame(1, $job->maxInstances);
        self::assertSame(1, $job->number);
        self::assertNull($job->lastUse);
        self::assertTrue($job->enable);
        self::assertGreaterThan(new DateTimeImmutable(), $job->nextRun);
        self::assertCount(0, $job->results);
    }

    public function testGetFullCommandWithoutArguments(): void
    {
        $job = new CronJob('test-command', '@daily');

        self::assertSame('test-command', $job->getFullCommand());
    }

    public function testGetFullCommandWithArguments(): void
    {
        $job = new CronJob('test-command', '@daily');
        $job->arguments = '--option=value';

        self::assertSame('test-command --option=value', $job->getFullCommand());
    }

    public function testIncreaseRunningInstances(): void
    {
        $job = new CronJob('test-command', '@daily');

        self::assertSame(0, $job->runningInstances);

        $job->increaseRunningInstances();
        self::assertSame(1, $job->runningInstances);

        $job->increaseRunningInstances();
        self::assertSame(2, $job->runningInstances);
    }

    public function testDecreaseRunningInstances(): void
    {
        $job = new CronJob('test-command', '@daily');
        $job->increaseRunningInstances();
        $job->increaseRunningInstances();

        self::assertSame(2, $job->runningInstances);

        $job->decreaseRunningInstances();
        self::assertSame(1, $job->runningInstances);

        $job->decreaseRunningInstances();
        self::assertSame(0, $job->runningInstances);
    }

    public function testEnable(): void
    {
        $job = new CronJob('test-command', '@daily');
        $job->enable = false;

        self::assertFalse($job->enable);

        $job->enable();
        self::assertTrue($job->enable);
    }

    public function testDisable(): void
    {
        $job = new CronJob('test-command', '@daily');

        self::assertTrue($job->enable);

        $job->disable();
        self::assertFalse($job->enable);
    }

    public function testCalculateNextRun(): void
    {
        $job = new CronJob('test-command', '0 0 * * *'); // Daily at midnight

        $initialNextRun = $job->nextRun;

        $job->calculateNextRun();

        // Next run should be set to a future date
        self::assertGreaterThan(new DateTimeImmutable(), $job->nextRun);
    }

    public function testToString(): void
    {
        $job = new CronJob('test-command', '@daily');

        self::assertSame('test-command', (string) $job);
    }

    public function testPropertySetters(): void
    {
        $job = new CronJob('test-command', '@daily');

        $job->arguments = '--verbose';
        self::assertSame('--verbose', $job->arguments);

        $job->description = 'Test description';
        self::assertSame('Test description', $job->description);

        $job->maxInstances = 5;
        self::assertSame(5, $job->maxInstances);

        $job->number = 2;
        self::assertSame(2, $job->number);

        $job->enable = false;
        self::assertFalse($job->enable);

        $job->period = '0 0 * * *';
        self::assertSame('0 0 * * *', $job->period);

        $lastUse = new DateTimeImmutable('2025-01-01 12:00:00');
        $job->lastUse = $lastUse;
        self::assertNotNull($job->lastUse);
        self::assertEquals('2025-01-01 12:00:00', $job->lastUse->format('Y-m-d H:i:s'));

        $nextRun = new DateTimeImmutable('2025-12-31 23:59:59');
        $job->nextRun = $nextRun;
        self::assertEquals('2025-12-31 23:59:59', $job->nextRun->format('Y-m-d H:i:s'));
    }

    public function testLastUseCanBeSetToNull(): void
    {
        $job = new CronJob('test-command', '@daily');
        $job->lastUse = new DateTimeImmutable();

        self::assertNotNull($job->lastUse);

        $job->lastUse = null;
        self::assertNull($job->lastUse);
    }
}
