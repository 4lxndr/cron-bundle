# Shapecode - Cron Bundle

[![PHP Version](https://img.shields.io/packagist/php-v/4lxndr/cron-bundle.svg)](https://packagist.org/packages/4lxndr/cron-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/4lxndr/cron-bundle.svg?label=stable)](https://packagist.org/packages/4lxndr/cron-bundle)
[![License](https://img.shields.io/packagist/l/4lxndr/cron-bundle.svg)](https://packagist.org/packages/4lxndr/cron-bundle)

A Symfony bundle for managing scheduled cron jobs within your application.

## Requirements

- PHP 8.4+
- Symfony 7.4+ or 8.0+

## Installation

Install via Composer:
```bash
composer require 4lxndr/cron-bundle
```

If Symfony Flex doesn't auto-register the bundle, add it to `config/bundles.php`:
```php
return [
    // ...
    Shapecode\Bundle\CronBundle\ShapecodeCronBundle::class => ['all' => true],
];
```

Update your database schema:
```bash
php bin/console doctrine:schema:update --force
```

## Usage

Create a Symfony console command and add the `AsCronJob` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCronJob('*/5 * * * *')]
class MyTaskCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:my-task');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Your task logic here

        return Command::SUCCESS;
    }
}
```

The cron expression (`*/5 * * * *`) follows standard crontab format. You can add multiple `AsCronJob` attributes to schedule the same command at different intervals.

Scan and register your cron jobs:
```bash
php bin/console shapecode:cron:scan
php bin/console shapecode:cron:run
```

## Automatic Execution

Add this to your system's crontab to run jobs automatically:
```bash
*/5 * * * * php /path/to/project/bin/console shapecode:cron:run
```

This executes the cron runner every 5 minutes. Jobs scheduled more frequently will be limited by this interval.

## Managing Jobs

Disable a cron job:
```bash
php bin/console shapecode:cron:disable app:my-task
```

Enable a cron job:
```bash
php bin/console shapecode:cron:enable app:my-task
```

Check job status:
```bash
php bin/console shapecode:cron:status
```

## Configuration

Configure a global timeout for all cron jobs (optional):

```yaml
# config/packages/shapecode_cron.yaml
shapecode_cron:
    timeout: null  # null = no timeout, or specify seconds as float (e.g., 300.0)
```
