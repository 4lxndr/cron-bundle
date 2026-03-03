<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Command;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Collection\CronJobCollection;
use Shapecode\Bundle\CronBundle\Command\CronVisualizeCommand;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function str_repeat;

#[CoversClass(CronVisualizeCommand::class)]
final class CronVisualizeCommandTest extends TestCase
{
    private CronJobRepository&Stub $cronJobRepository;

    private CronVisualizeCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cronJobRepository = self::createStub(CronJobRepository::class);
        $this->command           = new CronVisualizeCommand($this->cronJobRepository);
    }

    public function testCommandName(): void
    {
        self::assertSame('shapecode:cron:visualize', $this->command->getName());
    }

    public function testNoJobsFound(): void
    {
        $this->cronJobRepository->method('findAll')->willReturn([]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        self::assertStringContainsString('No cron jobs found', $tester->getDisplay());
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testInvalidDateReturnsFailure(): void
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => 'not-a-date']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid date format', $tester->getDisplay());
    }

    public function testDisabledJobShowsDashesOnly(): void
    {
        $job = new CronJob('my:command', '* * * * *');
        $job->disable();

        $this->cronJobRepository->method('findAll')->willReturn([$job]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        // All 96 timeline slots should be dashes; legend may contain other chars
        self::assertStringContainsString(str_repeat('-', 96), $tester->getDisplay());
    }

    public function testHourlyJobShowsRunMarkersAtEachHour(): void
    {
        // "0 * * * *" fires at HH:00 — every 4th 15-min slot
        $job = new CronJob('my:command', '0 * * * *');

        $this->cronJobRepository->method('findAll')->willReturn([$job]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        $display = $tester->getDisplay();

        self::assertStringContainsString('█', $display);
        // Each hour: █ at HH:00, then . for the next three 15-min slots
        self::assertStringContainsString('█...█...█...', $display);
    }

    public function testPauseWindowShowsBlockedSlots(): void
    {
        // Daily at midnight, paused 13:00–15:00 (8 slots of 15 min = 2 h)
        $job = new CronJob('my:command', '0 0 * * *');
        $job->addPauseWindow(new DateTimeImmutable('13:00'), new DateTimeImmutable('15:00'));

        $this->cronJobRepository->method('findAll')->willReturn([$job]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        $display = $tester->getDisplay();

        self::assertStringContainsString('█', $display);
        // 13:00–15:00 exclusive = 8 × 15-min slots of ░
        self::assertStringContainsString('░░░░░░░░', $display);
    }

    public function testRunMarkerTakesPriorityOverPauseWindow(): void
    {
        // Every-minute job: even within a pause window, runs are shown
        $job = new CronJob('my:command', '* * * * *');
        $job->addPauseWindow(new DateTimeImmutable('00:00'), new DateTimeImmutable('23:59'));

        $this->cronJobRepository->method('findAll')->willReturn([$job]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        // Runs happen every minute, so █ is rendered even inside the pause window
        self::assertStringContainsString('█', $tester->getDisplay());
    }

    public function testDateOptionAppearsInTitle(): void
    {
        $this->cronJobRepository->method('findAll')->willReturn([]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        self::assertStringContainsString('2026-03-03', $tester->getDisplay());
    }

    public function testDefaultDateIsTodayWhenNotSpecified(): void
    {
        $this->cronJobRepository->method('findAll')->willReturn([]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        self::assertStringContainsString($today, $tester->getDisplay());
    }

    public function testTagsOptionCallsFindByTags(): void
    {
        $job = new CronJob('my:command', '* * * * *');

        $repoMock = self::createMock(CronJobRepository::class);
        $repoMock
            ->expects(self::once())
            ->method('findByTags')
            ->with(['my-tag'])
            ->willReturn(new CronJobCollection($job));

        $command = new CronVisualizeCommand($repoMock);
        $tester  = new CommandTester($command);
        $tester->execute(['--date' => '2026-03-03', '--tags' => ['my-tag']]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testLegendIsAlwaysShown(): void
    {
        $job = new CronJob('my:command', '* * * * *');

        $this->cronJobRepository->method('findAll')->willReturn([$job]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        $display = $tester->getDisplay();

        self::assertStringContainsString('runs', $display);
        self::assertStringContainsString('paused', $display);
        self::assertStringContainsString('disabled', $display);
        self::assertStringContainsString('idle', $display);
    }

    public function testHourMarkersAppearsInHeader(): void
    {
        // The header is only rendered when jobs exist
        $this->cronJobRepository->method('findAll')->willReturn([new CronJob('my:command', '@daily')]);

        $tester = new CommandTester($this->command);
        $tester->execute(['--date' => '2026-03-03']);

        $display = $tester->getDisplay();

        self::assertStringContainsString('00  ', $display);
        self::assertStringContainsString('12  ', $display);
        self::assertStringContainsString('23  ', $display);
    }
}
