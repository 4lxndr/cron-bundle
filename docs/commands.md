---
title: Commands
---

# Commands

All commands are under the `shapecode:cron:` namespace.

## `shapecode:cron:scan`

Discovers cron jobs from `AsCronJob` attributes and syncs them to the database.

```bash
php bin/console shapecode:cron:scan
```

| Option | Description |
|---|---|
| `--keep-deleted` | Keep database entries for jobs no longer defined in code |
| `--default-disabled` | Register newly discovered jobs as disabled |

Run this command after adding or changing `AsCronJob` attributes. It detects and warns about circular dependencies.

---

## `shapecode:cron:run`

Executes all jobs whose scheduled time has passed.

```bash
php bin/console shapecode:cron:run
```

For each job the runner checks: enabled status → pause window → max concurrent instances → dependencies. Passing jobs are spawned as background processes.

Add to your system crontab to run automatically:

```
*/5 * * * * php /path/to/project/bin/console shapecode:cron:run
```

Jobs scheduled more frequently than the crontab interval will be limited by that interval.

---

## `shapecode:cron:status`

Displays a table of all registered cron jobs.

```bash
php bin/console shapecode:cron:status
php bin/console shapecode:cron:status --tags=critical
php bin/console shapecode:cron:status --show-dependencies
```

| Option | Short | Description |
|---|---|---|
| `--tags` | `-t` | Filter by one or more tags |
| `--show-dependencies` | `-d` | Add dependency columns to the table |

---

## `shapecode:cron:visualize`

Renders a day-view timeline showing when each job runs, is paused, or is disabled.

```bash
php bin/console shapecode:cron:visualize
php bin/console shapecode:cron:visualize --date 2026-03-10
php bin/console shapecode:cron:visualize --tags reporting
```

| Option | Short | Description |
|---|---|---|
| `--date` | `-d` | Date to visualize in `Y-m-d` format (default: today) |
| `--tags` | `-t` | Filter by one or more tags |

### Reading the output

The timeline spans the full 24-hour day in **15-minute slots** (96 columns total). Each slot uses one character:

| Symbol | Meaning |
|---|---|
| `█` (green) | Job fires in this 15-min slot |
| `░` (yellow) | Slot falls inside a [pause window](pause-windows.md) |
| `.` | Idle — job is enabled but not scheduled to run here |
| `-` (gray) | Job is disabled |

Hour markers appear above the timeline so you can orient yourself:

```
                                00  01  02  03  04  05  06  07  08  09  10  11  12  13  14  15  16  17  18  19  20  21  22  23
                                |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |   |
app:my-hourly-task              █...█...█...█...█...█...█...█...█...█...█...█...░░░░░░░░█...█...█...█...█...█...█...█...█...█...
app:my-daily-report             █...............................................░░░░░░░░..............................................
app:disabled-job                ------------------------------------------------------------------------------------------------

  █ runs   ░ paused   - disabled   . idle
```

In the example above `app:my-hourly-task` runs every hour, is paused from 13:00 to 15:00, and `app:my-daily-report` runs once at midnight and is also skipped during that same pause window.

---

## `shapecode:cron:enable`

Enables one or more cron jobs.

```bash
php bin/console shapecode:cron:enable app:my-task
php bin/console shapecode:cron:enable app:my-task --tags=critical
```

---

## `shapecode:cron:disable`

Disables one or more cron jobs.

```bash
php bin/console shapecode:cron:disable app:my-task
php bin/console shapecode:cron:disable app:my-task --tags=reporting
```
