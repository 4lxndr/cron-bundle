---
title: Tags
---

# Tags

Tags let you group and filter cron jobs across commands.

## Defining Tags

Use the `tags` parameter in the `AsCronJob` attribute:

```php
use Shapecode\Bundle\CronBundle\Attribute\AsCronJob;

#[AsCronJob(
    schedule: '0 1 * * *',
    tags: ['critical', 'reporting', 'nightly'],
)]
class GenerateReportCommand extends Command { }
```

## Filtering by Tag

Most commands accept `--tags` / `-t` (repeatable) to filter the jobs they act on.

```bash
# View only critical jobs
bin/console shapecode:cron:status --tags=critical

# View jobs that have both tags
bin/console shapecode:cron:status --tags=reporting --tags=nightly

# Visualize only reporting jobs for a specific date
bin/console shapecode:cron:visualize --tags=reporting --date=2026-03-10

# Enable / disable by tag
bin/console shapecode:cron:enable  my:command --tags=critical
bin/console shapecode:cron:disable my:command --tags=reporting
```

## Common Tag Conventions

| Category | Examples |
|---|---|
| Priority | `critical`, `high`, `medium`, `low` |
| Type | `reporting`, `cleanup`, `import`, `export` |
| Frequency | `hourly`, `daily`, `weekly`, `monthly` |
| Domain | `billing`, `inventory`, `analytics` |
