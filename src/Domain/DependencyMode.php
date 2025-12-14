<?php

// phpcs:disable

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Domain;

enum DependencyMode: string
{
    case AND = 'and';
    case OR = 'or';
}
