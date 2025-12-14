<?php

// phpcs:disable

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Domain;

enum DependencyFailureMode: string
{
    case SKIP = 'skip';
    case RUN = 'run';
    case DISABLE = 'disable';
}
