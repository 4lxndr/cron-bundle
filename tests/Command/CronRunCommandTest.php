<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Command;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Command\CronRunCommand;
use Shapecode\Bundle\CronBundle\CronJob\CommandHelper;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepository;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Kernel;

final class CronRunCommandTest extends TestCase
{
    private Kernel & Stub $kernel;

    private CommandHelper & Stub $commandHelper;

    private EntityManagerInterface & Stub $manager;

    private CronJobRepository & Stub $cronJobRepo;

    private CronJobResultRepository & Stub $cronJobResultRepo;

    private CronRunCommand $command;

    private InputInterface & Stub $input;

    private BufferedOutput $output;

    private MockClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel            = self::createStub(Kernel::class);
        $this->manager           = self::createStub(EntityManagerInterface::class);
        $this->commandHelper     = self::createStub(CommandHelper::class);
        $this->cronJobRepo       = self::createStub(CronJobRepository::class);
        $this->cronJobResultRepo = self::createStub(CronJobResultRepository::class);
        $this->input             = self::createStub(InputInterface::class);
        $this->output            = new BufferedOutput();

        $this->clock = new MockClock();

        $this->command = new CronRunCommand(
            $this->manager,
            $this->cronJobRepo,
            $this->cronJobResultRepo,
            $this->commandHelper,
            $this->clock,
        );
    }

    public function testRun(): void
    {
        $this->kernel->method('getProjectDir')->willReturn(__DIR__);

        $this->commandHelper->method('getConsoleBin')->willReturn('/bin/console');
        $this->commandHelper->method('getPhpExecutable')->willReturn('php');
        $this->commandHelper->method('getTimeout')->willReturn(null);

        $job = new CronJob('pwd', '* * * * *');
        $job->nextRun = new DateTime();

        $this->cronJobRepo->method('findAll')->willReturn([
            $job,
        ]);

        $this->command->run($this->input, $this->output);

        self::assertSame('shapecode:cron:run', $this->command->getName());
    }

    public function testRunWithTimeout(): void
    {
        $this->kernel->method('getProjectDir')->willReturn(__DIR__);

        $this->commandHelper->method('getConsoleBin')->willReturn('/bin/console');
        $this->commandHelper->method('getPhpExecutable')->willReturn('php');
        $this->commandHelper->method('getTimeout')->willReturn(30.0);

        $this->manager = self::createStub(EntityManagerInterface::class);

        $job = new CronJob('pwd', '* * * * *');
        $job->nextRun = new DateTime();

        $this->cronJobRepo->method('findAll')->willReturn([
            $job,
        ]);

        $this->command->run($this->input, $this->output);

        self::assertSame('shapecode:cron:run', $this->command->getName());
    }

    public function testRunWithResultRetention(): void
    {
        $this->kernel->method('getProjectDir')->willReturn(__DIR__);

        $this->commandHelper->method('getConsoleBin')->willReturn('/bin/console');
        $this->commandHelper->method('getPhpExecutable')->willReturn('php');
        $this->commandHelper->method('getTimeout')->willReturn(null);

        $job = new CronJob('pwd', '* * * * *');
        $job->nextRun = new DateTime();

        $this->cronJobRepo->method('findAll')->willReturn([
            $job,
        ]);

        // Set up a clock with a specific time
        $clock = new MockClock('2025-12-13 12:00:00');

        // Create a mock for the result repository to verify deleteOldLogs is called
        $cronJobResultRepoMock = self::createMock(CronJobResultRepository::class);

        // Expect deleteOldLogs to be called once with the correct threshold
        $cronJobResultRepoMock
            ->expects(self::once())
            ->method('deleteOldLogs')
            ->with(self::callback(function ($threshold): bool {
                // Should delete records older than 24 hours from now (2025-12-12 12:00:00)
                return $threshold instanceof DateTimeInterface
                    && $threshold->format('Y-m-d H:i:s') === '2025-12-12 12:00:00';
            }));

        // Create command with retention hours
        $command = new CronRunCommand(
            $this->manager,
            $this->cronJobRepo,
            $cronJobResultRepoMock,
            $this->commandHelper,
            $clock,
            24, // 24 hours retention
        );

        $command->run($this->input, $this->output);

        self::assertSame('shapecode:cron:run', $command->getName());
    }

    public function testRunWithoutResultRetention(): void
    {
        $this->kernel->method('getProjectDir')->willReturn(__DIR__);

        $this->commandHelper->method('getConsoleBin')->willReturn('/bin/console');
        $this->commandHelper->method('getPhpExecutable')->willReturn('php');
        $this->commandHelper->method('getTimeout')->willReturn(null);

        $job = new CronJob('pwd', '* * * * *');
        $job->nextRun = new DateTime();

        $this->cronJobRepo->method('findAll')->willReturn([
            $job,
        ]);

        // Create a mock for the result repository to verify deleteOldLogs is NOT called
        $cronJobResultRepoMock = self::createMock(CronJobResultRepository::class);

        // Expect deleteOldLogs to NOT be called
        $cronJobResultRepoMock
            ->expects(self::never())
            ->method('deleteOldLogs');

        // Create command without retention hours (null)
        $command = new CronRunCommand(
            $this->manager,
            $this->cronJobRepo,
            $cronJobResultRepoMock,
            $this->commandHelper,
            $this->clock,
            null, // No retention configured
        );

        $command->run($this->input, $this->output);

        self::assertSame('shapecode:cron:run', $command->getName());
    }
}
