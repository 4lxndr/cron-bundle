<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;

use function array_values;

/** @extends ArrayCollection<int, CronJobMetadata> */
final class CronJobMetadataCollection extends ArrayCollection
{
    public function __construct(
        CronJobMetadata ...$metadata,
    ) {
        parent::__construct(array_values($metadata));
    }
}
