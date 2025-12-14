<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function is_array;
use function is_string;
use function sprintf;

#[AsCommand(
    name: 'shapecode:cron:disable',
    description: 'Disables a cronjob',
)]
final class CronJobDisableCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CronJobRepository $cronJobRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('job', InputArgument::REQUIRED, 'Name or id of the job to disable')
            ->addOption('tags', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CronStyle($input, $output);

        $nameOrId = $input->getArgument('job');
        assert(is_string($nameOrId));

        $tags = $input->getOption('tags');
        assert(is_array($tags));
        /** @var list<string> $tags */
        $jobs = $tags === []
            ? $this->cronJobRepository->findByCommandOrId($nameOrId)
            : $this->cronJobRepository->findByCommandOrIdWithTags($nameOrId, $tags);

        if ($jobs->isEmpty()) {
            $io->error(sprintf('Couldn\'t find a job by the name or id of %s', $nameOrId));

            return Command::FAILURE;
        }

        foreach ($jobs as $job) {
            $job->disable();
            $this->entityManager->persist($job);
        }

        $this->entityManager->flush();

        $io->success('cron disabled');

        return Command::SUCCESS;
    }
}
