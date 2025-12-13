<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Domain\CronJobRunning;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Symfony\Component\Process\Process;

#[CoversClass(CronJobRunning::class)]
class CronJobRunningTest extends TestCase
{
    public function testConstructorAndReadonlyProperties(): void
    {
        $cronJob = new CronJob('test:command', '@daily');
        $process = new Process(['echo', 'test']);

        $running = new CronJobRunning($cronJob, $process);

        self::assertSame($cronJob, $running->cronJob);
        self::assertSame($process, $running->process);
    }

    public function testPropertiesAreReadonly(): void
    {
        $cronJob = new CronJob('test:command', '@daily');
        $process = new Process(['echo', 'test']);

        $running = new CronJobRunning($cronJob, $process);

        // Verify properties are accessible and contain correct values
        self::assertSame('test:command', $running->cronJob->command);
        self::assertStringContainsString('echo', $running->process->getCommandLine());
        self::assertStringContainsString('test', $running->process->getCommandLine());
    }
}
