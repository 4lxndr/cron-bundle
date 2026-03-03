# Shapecode - Cron Bundle

[![PHP Version](https://img.shields.io/packagist/php-v/4lxndr/cron-bundle.svg)](https://packagist.org/packages/4lxndr/cron-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/4lxndr/cron-bundle.svg?label=stable)](https://packagist.org/packages/4lxndr/cron-bundle)
[![License](https://img.shields.io/packagist/l/4lxndr/cron-bundle.svg)](https://packagist.org/packages/4lxndr/cron-bundle)

A Symfony bundle for managing scheduled cron jobs within your application.

## Acknowledgments

This bundle builds upon the foundation provided by [shapecode/cron-bundle](https://github.com/shapecode/cron-bundle). Thanks to the original authors and contributors for their excellent work.

## Requirements

- PHP 8.4+
- Symfony 7.4+ or 8.0+

## Installation

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

## Quick Start

Create a Symfony console command and add the `AsCronJob` attribute:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;
use Symfony\Component\Console\Command\Command;

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

Scan and run your jobs:

```bash
php bin/console shapecode:cron:scan
php bin/console shapecode:cron:run
```

Add to your system crontab to run automatically every 5 minutes:

```
*/5 * * * * php /path/to/project/bin/console shapecode:cron:run
```

## Documentation

- [Commands](commands.md) — Full reference for all available console commands
- [Configuration](configuration.md) — Bundle configuration options
- [Tags](tags.md) — Organizing and filtering jobs with tags
- [Dependencies](dependencies.md) — Job dependency chains, modes, and failure handling
- [Pause Windows](pause-windows.md) — Defining daily time ranges where jobs are silently skipped

## Database Migrations

When upgrading to a version that adds new columns (tags, dependencies, pause windows), migrate your database:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
bin/console shapecode:cron:scan
```
