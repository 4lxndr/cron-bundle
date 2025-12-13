<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\CronJob\CronJobManager;
use Shapecode\Bundle\CronBundle\Domain\CronJobCounter;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_search;
use function in_array;
use function sprintf;

#[AsCommand(
    name: 'shapecode:cron:scan',
    description: 'Scans for any new or deleted cron jobs',
)]
final class CronScanCommand extends Command
{
    public function __construct(
        private readonly CronJobManager $cronJobManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly CronJobRepository $cronJobRepository,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('keep-deleted', 'k', InputOption::VALUE_NONE, 'If set, deleted cron jobs will not be removed')
            ->addOption('default-disabled', 'd', InputOption::VALUE_NONE, 'If set, new jobs will be disabled by default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CronStyle($input, $output);
        $io->comment(sprintf('Scan for cron jobs started at %s', $this->clock->now()->format('r')));
        $io->title('scanning ...');

        $keepDeleted = (bool) $input->getOption('keep-deleted');
        $defaultDisabled = (bool) $input->getOption('default-disabled');

        // List the known jobs
        $cronJobs = $this->cronJobRepository->findAllCollection();
        $knownJobs = $cronJobs->mapToCommand();

        $counter = new CronJobCounter();
        foreach ($this->cronJobManager->getJobs() as $jobMetadata) {
            $command = $jobMetadata->command;

            $io->section($command);

            $counter->increase($jobMetadata);

            if (in_array($command, $knownJobs, true)) {
                // Clear it from the known jobs so that we don't try to delete it
                $key = array_search($command, $knownJobs, true);
                if ($key !== false) {
                    unset($knownJobs[$key]);
                }

                // Update the job if necessary
                $currentJob = $this->cronJobRepository->findOneByCommand($command, $counter->value($jobMetadata));

                if ($currentJob === null) {
                    continue;
                }

                $currentJob->description = $jobMetadata->description;
                $currentJob->arguments = $jobMetadata->arguments;

                $io->text(sprintf('command: %s', $jobMetadata->command));
                $io->text(sprintf('arguments: %s', $jobMetadata->arguments));
                $io->text(sprintf('expression: %s', $jobMetadata->expression));
                $io->text(sprintf('instances: %s', $jobMetadata->maxInstances));

                if ($currentJob->period !== $jobMetadata->expression || $currentJob->maxInstances !== $jobMetadata->maxInstances) {
                    $currentJob->period = $jobMetadata->expression;
                    $currentJob->arguments = $jobMetadata->arguments;
                    $currentJob->maxInstances = $jobMetadata->maxInstances;

                    $currentJob->calculateNextRun();
                    $io->notice('cronjob updated');
                }
            } else {
                $this->newJobFound($io, $jobMetadata, $defaultDisabled, $counter->value($jobMetadata));
            }
        }

        $io->success('Finished scanning for cron jobs');

        // Clear any jobs that weren't found
        if ($keepDeleted === false) {
            $io->title('remove cron jobs');

            if ($knownJobs !== []) {
                foreach ($knownJobs as $deletedJob) {
                    $io->notice(sprintf('Deleting job: %s', $deletedJob));
                    $jobsToDelete = $this->cronJobRepository->findByCommandOrId($deletedJob);
                    foreach ($jobsToDelete as $jobToDelete) {
                        $this->entityManager->remove($jobToDelete);
                    }
                }
            } else {
                $io->info('No cronjob has to be removed.');
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function newJobFound(CronStyle $io, CronJobMetadata $metadata, bool $defaultDisabled, int $counter): void
    {
        $newJob = new CronJob($metadata->command, $metadata->expression);
        $newJob->arguments = $metadata->arguments;
        $newJob->description = $metadata->description;
        $newJob->enable = !$defaultDisabled;
        $newJob->number = $counter;
        $newJob->calculateNextRun();

        $io->success(sprintf(
            'Found new job: "%s" with period %s',
            $newJob->getFullCommand(),
            $newJob->period,
        ));

        $this->entityManager->persist($newJob);
    }
}
