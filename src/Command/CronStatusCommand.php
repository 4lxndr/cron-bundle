<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function assert;
use function implode;
use function is_array;

#[AsCommand(
    name: 'shapecode:cron:status',
    description: 'Displays the current status of cron jobs',
)]
final class CronStatusCommand extends Command
{
    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('tags', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by tags')
            ->addOption('show-dependencies', 'd', InputOption::VALUE_NONE, 'Show dependency information');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CronStyle($input, $output);

        $io->title('Cron job status');

        $tags = $input->getOption('tags');
        assert($tags !== null && is_array($tags));
        /** @var list<string> $tags */
        $showDeps = (bool) $input->getOption('show-dependencies');

        $jobs = $tags === []
            ? $this->cronJobRepository->findAll()
            : $this->cronJobRepository->findByTags($tags)->toArray();

        $headers = [
            'ID',
            'Command',
            'Tags',
            'Next Schedule',
            'Last run',
            'Enabled',
        ];

        if ($showDeps) {
            $headers[] = 'Dependencies';
            $headers[] = 'Dep Mode';
            $headers[] = 'On Failure';
        }

        $tableContent = array_map(
            function (CronJob $cronJob) use ($showDeps): array {
                $row = [
                    $cronJob->getId(),
                    $cronJob->getFullCommand(),
                    implode(', ', $cronJob->tags),
                    $cronJob->enable ? $cronJob->nextRun->format('r') : 'Not scheduled',
                    $cronJob->lastUse?->format('r') ?? 'This job has not yet been run',
                    $cronJob->enable ? 'Enabled' : 'Disabled',
                ];

                if ($showDeps) {
                    $depNames = array_map(
                        static fn (CronJob $dep): string => $dep->command,
                        $cronJob->dependencies->toArray(),
                    );
                    $row[] = implode(', ', $depNames);
                    $row[] = $cronJob->dependencyMode->value;
                    $row[] = $cronJob->onDependencyFailure->value;
                }

                return $row;
            },
            $jobs,
        );

        $io->table($headers, $tableContent);

        return Command::SUCCESS;
    }
}
