---
title: Pause Windows
---

# Pause Windows

Pause windows define daily time ranges during which a job is silently skipped, even if its cron expression says it is due. The start time is **inclusive** and the end time is **exclusive**.

## Defining Pause Windows via Attribute

Use `pauseWindows` with `['HH:MM', 'HH:MM']` pairs:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;

#[AsCronJob(
    schedule: '0 * * * *',
    pauseWindows: [['13:00', '15:00'], ['22:00', '23:30']],
)]
class MyHourlyCommand extends Command
{
    // Runs every hour, except between 13:00–15:00 and 22:00–23:30
}
```

## Defining Pause Windows Programmatically

Call `addPauseWindow()` on the `CronJob` entity directly:

```php
$job->clearPauseWindows();
$job->addPauseWindow(new DateTimeImmutable('13:00'), new DateTimeImmutable('15:00'));
```

## Overnight Windows

A window where `from > to` spans midnight:

```php
#[AsCronJob(
    schedule: '0 * * * *',
    pauseWindows: [['22:00', '06:00']],  // pauses from 22:00 until 06:00 the next day
)]
class NightlyCommand extends Command { }
```

## How Pause Windows Work

1. When `shapecode:cron:run` executes, each job is checked against its configured pause windows using the current wall-clock time.
2. If the current time falls inside any window, the job is skipped with the notice: *"cronjob will not be executed. Currently in a pause window."*
3. The next-run time is **not** recalculated — the job simply skips that occurrence and runs normally at its next scheduled time outside the window.
4. Pause windows are synced from the attribute to the database each time `shapecode:cron:scan` runs.

## Visualizing Pause Windows

Use [`shapecode:cron:visualize`](commands.md#shapecodecronvisualize) to see paused slots (shown as `░`) alongside run times in a full-day timeline.

```bash
bin/console shapecode:cron:visualize
bin/console shapecode:cron:visualize --date 2026-03-10
```
