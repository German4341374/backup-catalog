# Data model and health rules

## Systems

A system represents a server or business application whose backup posture matters. `expected_backup_interval_hours` is measured from the completion time of the latest successful run. A system is:

- **Healthy** when its latest successful completion plus the configured interval is still in the future.
- **Overdue** when that deadline is in the past.
- **Never backed up** when no successful run exists.

Criticality does not change the calculation. It controls ordering so operators see critical systems first.

## Backup jobs

A job describes a known schedule, backup type, and expected maximum runtime. A run may omit a job when an external source cannot provide that mapping, but it must always reference a system. If a job is supplied, application validation ensures it belongs to that same system.

## Backup runs

Runs are append-only observations in normal workflows. `external_id` is unique and required by bulk imports, which makes retrying the same import safe. Manual REST/UI creation may omit it.

Database constraints enforce allowed types and statuses, non-negative sizes, completion after start, and a completion timestamp for every non-running status. A partial index accelerates latest-success lookups, and another targets overdue-running scans.

## Why no deletion endpoint for runs?

Backup history should not disappear through the routine UI or API. Corrections should be handled through a documented administrative database process with an audit record in a real deployment. The sample project therefore exposes registration and read/export operations only.
