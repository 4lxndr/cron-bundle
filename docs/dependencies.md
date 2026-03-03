---
title: Dependencies
---

# Dependencies

Dependencies let you chain jobs so that a job only runs after its prerequisites have completed successfully.

## Defining Dependencies

Use the `dependsOn` parameter with PHP class names:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;

// Step 1 – no dependencies
#[AsCronJob(schedule: '0 1 * * *')]
class DataImportCommand extends Command { }

// Step 2 – runs only after DataImportCommand succeeded
#[AsCronJob(schedule: '0 2 * * *', dependsOn: [DataImportCommand::class])]
class ProcessDataCommand extends Command { }

// Step 3 – runs only after ProcessDataCommand succeeded
#[AsCronJob(schedule: '0 3 * * *', dependsOn: [ProcessDataCommand::class])]
class GenerateReportCommand extends Command { }
```

**How it works:** before running a job the system checks that every dependency completed with exit code `0` in its last recorded run. A job that has never run is treated as unsatisfied.

## Dependency Modes

When a job has multiple dependencies, `dependencyMode` controls the logic.

### AND (default)

All dependencies must have succeeded:

```php
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;

#[AsCronJob(
    schedule: '0 4 * * *',
    dependsOn: [ImportCommand::class, ValidateCommand::class],
    dependencyMode: DependencyMode::AND,
)]
class ProcessCommand extends Command
{
    // Runs ONLY if BOTH ImportCommand AND ValidateCommand succeeded
}
```

### OR

At least one dependency must have succeeded:

```php
#[AsCronJob(
    schedule: '0 5 * * *',
    dependsOn: [PrimarySourceCommand::class, BackupSourceCommand::class],
    dependencyMode: DependencyMode::OR,
)]
class ConsumeDataCommand extends Command
{
    // Runs if EITHER PrimarySourceCommand OR BackupSourceCommand succeeded
}
```

## Failure Handling

`onDependencyFailure` controls what happens when dependencies are not satisfied.

### SKIP (default)

Skip this run silently; try again next time:

```php
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;

#[AsCronJob(
    schedule: '*/5 * * * *',
    dependsOn: [HealthCheckCommand::class],
    onDependencyFailure: DependencyFailureMode::SKIP,
)]
class MonitorCommand extends Command { }
```

### RUN

Run anyway, ignoring unsatisfied dependencies:

```php
#[AsCronJob(
    schedule: '0 * * * *',
    dependsOn: [CacheWarmupCommand::class],
    onDependencyFailure: DependencyFailureMode::RUN,
)]
class CleanupCommand extends Command
{
    // Always runs — useful for cleanup that must happen regardless
}
```

### DISABLE

Automatically disable the job until it is manually re-enabled:

```php
#[AsCronJob(
    schedule: '0 0 * * *',
    dependsOn: [RequiredSetupCommand::class],
    onDependencyFailure: DependencyFailureMode::DISABLE,
)]
class CriticalProcessCommand extends Command
{
    // Gets disabled on failure; re-enable with:
    // bin/console shapecode:cron:enable critical:process
}
```

## Viewing Dependencies

```bash
bin/console shapecode:cron:status --show-dependencies
bin/console shapecode:cron:status --tags=critical --show-dependencies
```

The table gains three extra columns: **Dependencies**, **Dep Mode**, and **On Failure**.

## Troubleshooting

### Circular Dependencies

`shapecode:cron:scan` detects cycles and prints a warning:

```
Circular dependencies detected:
  job-a -> job-b -> job-c -> job-a
```

Redesign the dependency graph to remove the cycle.

### Missing Dependency Warning

```
Job "process:data" depends on "import:data" which was not found
```

Causes: typo in the class name, command not registered as a service, or command not yet scanned.

### Job Never Runs

1. Run `bin/console shapecode:cron:status --show-dependencies`
2. Check the last-run time of each dependency
3. Verify the dependency mode (AND vs OR)
4. Check whether the job was auto-disabled (`onDependencyFailure: DISABLE`)
