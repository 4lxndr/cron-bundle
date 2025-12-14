<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Shapecode\Bundle\CronBundle\Collection\CronJobRunningCollection;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\CronJob\CommandHelper;
use Shapecode\Bundle\CronBundle\CronJob\DependencyResolver;
use Shapecode\Bundle\CronBundle\Domain\CronJobRunning;
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function count;
use function sleep;
use function sprintf;

#[AsCommand(
    name: CronRunCommand::NAME,
    description: 'Runs any currently schedule cron jobs',
)]
class CronRunCommand extends Command
{
    public const NAME = 'shapecode:cron:run';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CronJobRepository $cronJobRepository,
        private readonly CronJobResultRepository $cronJobResultRepository,
        private readonly CommandHelper $commandHelper,
        private readonly ClockInterface $clock,
        private readonly DependencyResolver $dependencyResolver,
        private readonly ?int $resultRetentionHours = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new CronStyle($input, $output);
        $now = $this->clock->now();

        // Clean up old CronJobResult records if retention hours is configured
        if ($this->resultRetentionHours !== null) {
            $retentionThreshold = $now->sub(new DateInterval(sprintf('PT%dH', $this->resultRetentionHours)));
            $this->cronJobResultRepository->deleteOldLogs($retentionThreshold);
        }

        $jobsToRun = $this->cronJobRepository->findAll();

        $jobCount = count($jobsToRun);
        $style->comment(sprintf('Cron jobs started at %s', $now->format('r')));

        $style->title('Execute cron jobs');
        $style->info(sprintf('Found %d jobs', $jobCount));

        $processes = new CronJobRunningCollection();

        foreach ($jobsToRun as $job) {
            $style->section(sprintf('Running "%s"', $job->getFullCommand()));

            if (!$job->enable) {
                $style->notice('cronjob is disabled');

                continue;
            }

            if ($job->nextRun > $now) {
                $style->notice(sprintf('cronjob will not be executed. Next run is: %s', $job->nextRun->format('r')));

                continue;
            }

            if ($job->runningInstances >= $job->maxInstances) {
                $style->notice('cronjob will not be executed. The number of maximum instances has been exceeded.');
                continue;
            }

            // Check dependencies
            $dependencyCheck = $this->dependencyResolver->canJobRun($job);
            if (!$dependencyCheck['canRun']) {
                $style->notice(sprintf(
                    'cronjob will not be executed. %s',
                    $dependencyCheck['reason'],
                ));

                // Handle based on failure mode
                match ($job->onDependencyFailure) {
                    DependencyFailureMode::SKIP => null, // Already skipping
                    DependencyFailureMode::DISABLE => (function () use ($job, $style): void {
                        $job->disable();
                        $this->entityManager->persist($job);
                        $this->entityManager->flush();
                        $style->warning('Job has been disabled due to dependency failure.');
                    })(),
                    DependencyFailureMode::RUN => (function () use ($style): void {
                        $style->notice('Running anyway due to onDependencyFailure=RUN setting.');
                    })(),
                };

                if ($job->onDependencyFailure !== DependencyFailureMode::RUN) {
                    continue;
                }
            }

            $job->increaseRunningInstances();
            $process = $this->runJob($job);

            $job->lastUse = $now;

            $this->entityManager->persist($job);
            $this->entityManager->flush();

            $processes->add(new CronJobRunning($job, $process));

            $style->success('cronjob started successfully and is running in background');
        }

        $style->section('Summary');

        if ($processes->isEmpty()) {
            $style->info('No jobs were executed.');

            return Command::SUCCESS;
        }

        $style->text('waiting for all running jobs ...');

        $this->waitProcesses($processes);

        $style->success('All jobs are finished.');

        return Command::SUCCESS;
    }

    private function waitProcesses(CronJobRunningCollection $processes): void
    {
        while (count($processes) > 0) {
            foreach ($processes as $running) {
                try {
                    $running->process->checkTimeout();

                    if ($running->process->isRunning() === true) {
                        continue;
                    }
                } catch (ProcessTimedOutException) {
                }

                $job = $running->cronJob;
                $this->entityManager->refresh($job);
                $job->decreaseRunningInstances();

                if ($job->runningInstances === 0) {
                    $job->calculateNextRun();
                }

                $this->entityManager->persist($job);
                $this->entityManager->flush();

                $processes->removeElement($running);
            }

            sleep(1);
        }
    }

    private function runJob(CronJob $job): Process
    {
        $command = [
            $this->commandHelper->getPhpExecutable(),
            $this->commandHelper->getConsoleBin(),
            CronProcessCommand::NAME,
            $job->getId(),
        ];

        $process = new Process($command);
        $process->disableOutput();

        $timeout = $this->commandHelper->getTimeout();
        if ($timeout > 0) {
            $process->setTimeout($timeout);
        }

        $process->start();

        return $process;
    }
}
