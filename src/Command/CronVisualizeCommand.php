<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Command;

use Cron\CronExpression;
use DateTimeImmutable;
use Exception;
use Shapecode\Bundle\CronBundle\Console\Style\CronStyle;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function assert;
use function is_array;
use function is_string;
use function sprintf;
use function str_pad;
use function str_repeat;
use function substr;

#[AsCommand(
    name: 'shapecode:cron:visualize',
    description: 'Visualizes when cron jobs run throughout the day',
)]
final class CronVisualizeCommand extends Command
{
    private const int SLOTS_PER_DAY = 96; // 24h × 4 slots/h (15 min each)
    private const int SLOT_MINUTES = 15;
    private const int LABEL_WIDTH = 32;

    public function __construct(
        private readonly CronJobRepository $cronJobRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date to visualize (Y-m-d), defaults to today')
            ->addOption('tags', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter by tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new CronStyle($input, $output);

        $dateStr = $input->getOption('date');
        assert($dateStr === null || is_string($dateStr));

        $tags = $input->getOption('tags');
        assert($tags !== null && is_array($tags));
        /** @var list<string> $tags */
        try {
            $base = $dateStr !== null
                ? new DateTimeImmutable($dateStr)
                : new DateTimeImmutable('today');
            $date = new DateTimeImmutable($base->format('Y-m-d').' 00:00:00');
        } catch (Exception) {
            $io->error('Invalid date format. Use Y-m-d (e.g. 2026-03-03).');

            return Command::FAILURE;
        }

        $io->title('Cron Job Schedule – '.$date->format('D, Y-m-d'));

        $jobs = $tags === []
            ? $this->cronJobRepository->findAll()
            : $this->cronJobRepository->findByTags($tags)->toArray();

        if ($jobs === []) {
            $io->warning('No cron jobs found.');

            return Command::SUCCESS;
        }

        $this->renderTimeline($io, $jobs, $date);

        $io->newLine();
        $io->writeln('  <fg=green>█</> runs   <fg=yellow>░</> paused   <fg=gray>-</> disabled   . idle');

        return Command::SUCCESS;
    }

    /** @param CronJob[] $jobs */
    private function renderTimeline(CronStyle $io, array $jobs, DateTimeImmutable $date): void
    {
        $labelWidth = self::LABEL_WIDTH;

        // Hour labels: "00  01  02  …" — each hour occupies 4 chars (1 per slot)
        $header = str_repeat(' ', $labelWidth);
        for ($h = 0; $h < 24; ++$h) {
            $header .= sprintf('%02d  ', $h);
        }

        $io->writeln($header);

        // Separator with a tick mark at the start of each hour
        $separator = str_repeat(' ', $labelWidth);
        for ($h = 0; $h < 24; ++$h) {
            $separator .= '|   ';
        }

        $io->writeln($separator);

        foreach ($jobs as $job) {
            $label = substr($job->getFullCommand(), 0, $labelWidth - 2);
            $label = str_pad($label, $labelWidth);

            $io->writeln($label.$this->buildColoredTimeline($job, $date));
        }
    }

    private function buildColoredTimeline(CronJob $job, DateTimeImmutable $date): string
    {
        $timeline = '';
        $cron = new CronExpression($job->period);

        for ($slot = 0; $slot < self::SLOTS_PER_DAY; ++$slot) {
            $slotStart = $date->modify(sprintf('+%d minutes', $slot * self::SLOT_MINUTES));
            $slotEnd = $slotStart->modify(sprintf('+%d minutes', self::SLOT_MINUTES));

            if (!$job->enable) {
                $timeline .= '<fg=gray>-</>';
                continue;
            }

            $fires = false;
            try {
                $nextRun = DateTimeImmutable::createFromMutable(
                    $cron->getNextRunDate($slotStart->format('Y-m-d H:i:s'), 0, true),
                );
                $fires = $nextRun < $slotEnd;
            } catch (Exception) {
                // Non-periodic expression (e.g. @reboot) — treat as no run
            }

            if ($job->isInPauseWindow($slotStart)) {
                $timeline .= '<fg=yellow>░</>';
            } elseif ($fires) {
                $timeline .= '<fg=green>█</>';
            } else {
                $timeline .= '.';
            }
        }

        return $timeline;
    }
}
