<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Shapecode\Bundle\CronBundle\Entity\CronJob;

use function array_map;
use function array_values;

/** @extends ArrayCollection<int, CronJob> */
final class CronJobCollection extends ArrayCollection
{
    public function __construct(
        CronJob ...$cronJob,
    ) {
        parent::__construct(array_values($cronJob));
    }

    /** @return array<int, string> */
    public function mapToCommand(): array
    {
        return array_map(static fn (CronJob $o): string => $o->command, $this->toArray());
    }
}
