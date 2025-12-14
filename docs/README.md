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

Configure global settings for cron jobs (optional):

```yaml
# config/packages/shapecode_cron.yaml
shapecode_cron:
    timeout: null  # null = no timeout, or specify seconds as float (e.g., 300.0)
    result_retention_hours: null  # null = keep all results, or specify hours as integer (e.g., 168 for 7 days)
```

### Configuration Options

- **timeout**: Global timeout for cron job execution in seconds. Set to `null` for no timeout, or specify a float value (e.g., `300.0` for 5 minutes).
- **result_retention_hours**: Automatic cleanup period for `CronJobResult` records in hours. When set, old results will be automatically deleted during cron runs. Set to `null` to keep all results indefinitely, or specify an integer value (e.g., `168` for 7 days, `720` for 30 days).

## Job Tags and Dependencies

Tags and dependencies provide powerful ways to organize and orchestrate your cron jobs.

### Job Tagging

Tags allow you to organize and group cron jobs for easier management.

#### Adding Tags to Jobs

Use the `tags` parameter in the `AsCronJob` attribute:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;
use Symfony\Component\Console\Command\Command;

#[AsCronJob(
    schedule: '0 1 * * *',
    tags: ['critical', 'reporting', 'nightly']
)]
class GenerateReportCommand extends Command
{
    // ... command implementation
}
```

#### Filtering by Tags

**View jobs by tag:**
```bash
bin/console shapecode:cron:status --tags=critical
bin/console shapecode:cron:status --tags=reporting --tags=nightly
```

**Enable/disable jobs by tag:**
```bash
bin/console shapecode:cron:enable my:command --tags=critical
bin/console shapecode:cron:disable my:command --tags=reporting
```

#### Tag Use Cases

- **Priority levels**: `critical`, `high`, `medium`, `low`
- **Categories**: `reporting`, `cleanup`, `import`, `export`
- **Schedules**: `hourly`, `daily`, `weekly`, `monthly`
- **Business domains**: `billing`, `inventory`, `analytics`

### Job Dependencies

Dependencies allow you to create execution chains where jobs run only after their dependencies complete successfully.

#### Defining Dependencies

Use the `dependsOn` parameter with PHP class names:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;

// This job has no dependencies
#[AsCronJob(schedule: '0 1 * * *')]
class DataImportCommand extends Command
{
    // ... imports data
}

// This job depends on DataImportCommand
#[AsCronJob(
    schedule: '0 2 * * *',
    dependsOn: [DataImportCommand::class]
)]
class ProcessDataCommand extends Command
{
    // ... processes imported data
}

// This job depends on ProcessDataCommand
#[AsCronJob(
    schedule: '0 3 * * *',
    dependsOn: [ProcessDataCommand::class]
)]
class GenerateReportCommand extends Command
{
    // ... generates report from processed data
}
```

#### How Dependencies Work

1. **Dependency Check**: Before running a job, the system checks if all dependencies completed successfully (exit code 0) in their last run
2. **Execution Decision**: Based on the dependency check and configured mode, the job either runs or is skipped
3. **Failure Handling**: If dependencies fail, the system handles it according to the `onDependencyFailure` setting

### Dependency Modes

Control how multiple dependencies are evaluated using `DependencyMode`.

#### AND Mode (Default)

ALL dependencies must succeed for the job to run:

```php
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;

#[AsCronJob(
    schedule: '0 4 * * *',
    dependsOn: [ImportCommand::class, ValidateCommand::class],
    dependencyMode: DependencyMode::AND  // default
)]
class ProcessCommand extends Command
{
    // Runs ONLY if BOTH ImportCommand AND ValidateCommand succeeded
}
```

#### OR Mode

ANY dependency can succeed for the job to run:

```php
#[AsCronJob(
    schedule: '0 5 * * *',
    dependsOn: [PrimarySourceCommand::class, BackupSourceCommand::class],
    dependencyMode: DependencyMode::OR
)]
class ConsumeDataCommand extends Command
{
    // Runs if EITHER PrimarySourceCommand OR BackupSourceCommand succeeded
}
```

### Failure Handling

Control what happens when dependencies fail using `DependencyFailureMode`.

#### SKIP Mode (Default)

Skip execution and try again next time:

```php
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;

#[AsCronJob(
    schedule: '*/5 * * * *',
    dependsOn: [HealthCheckCommand::class],
    onDependencyFailure: DependencyFailureMode::SKIP  // default
)]
class MonitorCommand extends Command
{
    // Skips this execution, will check again in 5 minutes
}
```

#### RUN Mode

Run anyway, ignoring dependency failures:

```php
#[AsCronJob(
    schedule: '0 * * * *',
    dependsOn: [CacheWarmupCommand::class],
    onDependencyFailure: DependencyFailureMode::RUN
)]
class CleanupCommand extends Command
{
    // Always runs, even if CacheWarmupCommand failed
    // Useful for cleanup jobs that should always execute
}
```

#### DISABLE Mode

Automatically disable the job until manually re-enabled:

```php
#[AsCronJob(
    schedule: '0 0 * * *',
    dependsOn: [RequiredSetupCommand::class],
    onDependencyFailure: DependencyFailureMode::DISABLE
)]
class CriticalProcessCommand extends Command
{
    // Job gets disabled if RequiredSetupCommand fails
    // Must be manually re-enabled with: bin/console shapecode:cron:enable critical:process
}
```

### Advanced Usage Examples

#### Example 1: Simple Dependency Chain

```php
// Step 1: Download data
#[AsCronJob(schedule: '0 1 * * *')]
class DownloadDataCommand extends Command { }

// Step 2: Validate data (depends on download)
#[AsCronJob(
    schedule: '0 2 * * *',
    dependsOn: [DownloadDataCommand::class]
)]
class ValidateDataCommand extends Command { }

// Step 3: Import data (depends on validation)
#[AsCronJob(
    schedule: '0 3 * * *',
    dependsOn: [ValidateDataCommand::class]
)]
class ImportDataCommand extends Command { }
```

#### Example 2: OR Dependencies with Fallback

```php
// Primary data source
#[AsCronJob(schedule: '0 1 * * *', tags: ['data-import'])]
class PrimaryApiImportCommand extends Command { }

// Backup data source
#[AsCronJob(schedule: '0 1 * * *', tags: ['data-import'])]
class BackupApiImportCommand extends Command { }

// Process data from either source
#[AsCronJob(
    schedule: '0 2 * * *',
    dependsOn: [
        PrimaryApiImportCommand::class,
        BackupApiImportCommand::class
    ],
    dependencyMode: DependencyMode::OR,
    tags: ['data-processing']
)]
class ProcessDataCommand extends Command
{
    // Runs if either primary or backup import succeeded
}
```

#### Example 3: Complex Workflow with Tags

```php
// Data import jobs (tagged for grouping)
#[AsCronJob(schedule: '0 1 * * *', tags: ['import', 'critical'])]
class ImportCustomersCommand extends Command { }

#[AsCronJob(schedule: '0 1 * * *', tags: ['import', 'critical'])]
class ImportOrdersCommand extends Command { }

#[AsCronJob(schedule: '0 1 * * *', tags: ['import', 'normal'])]
class ImportProductsCommand extends Command { }

// Processing job (depends on all imports, AND mode)
#[AsCronJob(
    schedule: '0 2 * * *',
    dependsOn: [
        ImportCustomersCommand::class,
        ImportOrdersCommand::class,
        ImportProductsCommand::class
    ],
    dependencyMode: DependencyMode::AND,
    onDependencyFailure: DependencyFailureMode::SKIP,
    tags: ['processing', 'critical']
)]
class SyncDatabaseCommand extends Command
{
    // Only runs if ALL imports succeeded
}

// Reporting (depends on processing, but runs anyway for partial data)
#[AsCronJob(
    schedule: '0 3 * * *',
    dependsOn: [SyncDatabaseCommand::class],
    onDependencyFailure: DependencyFailureMode::RUN,
    tags: ['reporting', 'normal']
)]
class GenerateDailyReportCommand extends Command
{
    // Always runs, even if sync failed (reports on available data)
}
```

### Viewing Dependencies

**Show dependencies in status:**
```bash
bin/console shapecode:cron:status --show-dependencies
```

Output includes:
- Dependencies column: Lists jobs this job depends on
- Dep Mode column: Shows AND or OR
- On Failure column: Shows skip, run, or disable

**Filter by tags:**
```bash
bin/console shapecode:cron:status --tags=critical --show-dependencies
```

### Troubleshooting

#### Circular Dependencies

If you see circular dependency warnings during scan:

```
Circular dependencies detected:
  job-a -> job-b -> job-c -> job-a
```

**Solution**: Redesign your dependency graph to remove cycles.

#### Missing Dependencies

If you see missing dependency warnings:

```
Job "process:data" depends on "import:data" which was not found
```

**Causes**:
- Typo in class name
- Command class not registered as a service
- Command not scanned yet

**Solution**: Verify the class name and ensure the command is properly registered.

#### Job Never Runs

If a job never runs despite being enabled:

1. Check dependencies: `bin/console shapecode:cron:status --show-dependencies`
2. Check last run of dependencies
3. Verify dependency mode (AND vs OR)
4. Check if job was auto-disabled (failure mode = DISABLE)

### Database Migration

When upgrading to a version with tags and dependencies support, you need to migrate your database:

```bash
# Generate migration
bin/console doctrine:migrations:diff

# Review and run migration
bin/console doctrine:migrations:migrate

# Scan jobs to populate new fields
bin/console shapecode:cron:scan
```
