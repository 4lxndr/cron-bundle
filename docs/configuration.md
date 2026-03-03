---
title: Configuration
---

# Configuration

Create `config/packages/shapecode_cron.yaml` to configure the bundle:

```yaml
shapecode_cron:
    timeout: null
    result_retention_hours: null
```

Both options are optional and default to `null`.

## Options

### `timeout`

Global execution timeout for cron jobs in seconds.

| Value | Behavior |
|---|---|
| `null` (default) | No timeout — jobs run until they finish |
| `300.0` | Kill the job process after 5 minutes |

```yaml
shapecode_cron:
    timeout: 300.0  # 5 minutes
```

### `result_retention_hours`

How long to keep `CronJobResult` records. Old results are deleted at the start of each `shapecode:cron:run`.

| Value | Behavior |
|---|---|
| `null` (default) | Keep all results forever |
| `168` | Delete results older than 7 days |
| `720` | Delete results older than 30 days |

```yaml
shapecode_cron:
    result_retention_hours: 168  # 7 days
```
