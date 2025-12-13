<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Shapecode\Bundle\CronBundle\Domain\CronJobRunning;

use function array_values;

/** @extends ArrayCollection<int, CronJobRunning> */
final class CronJobRunningCollection extends ArrayCollection
{
    public function __construct(
        CronJobRunning ...$runnings,
    ) {
        parent::__construct(array_values($runnings));
    }
}
