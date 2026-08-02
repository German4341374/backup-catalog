# Operations runbook

## Health check fails

1. Inspect container state: `docker compose ps`.
2. Read application and database logs: `docker compose logs --tail=200 app db nginx`.
3. Check PostgreSQL readiness: `docker compose exec db pg_isready -U backup_catalog -d backup_catalog`.
4. Confirm the application receives `DATABASE_DSN`, `DATABASE_USER`, and `DATABASE_PASSWORD` without printing their values: `docker compose exec app php -r 'echo getenv("DATABASE_DSN") ? "dsn set\n" : "dsn missing\n";'`.
5. Re-run migrations: `docker compose exec app php bin/console migrate`.

Do not delete the volume as a troubleshooting shortcut. Take a database dump before any destructive recovery.

## Migration checksum mismatch

An applied SQL migration was edited. Restore the committed migration contents and create a new higher-numbered migration for the desired change. Never update `schema_migrations.checksum` manually.

## Import rejected

The import is atomic for new records: one invalid record prevents all new records from being inserted. Read the field paths in the JSON error, correct the source, and retry. Existing `externalId` values are skipped. Verify that `system` and `job` match names already in the catalog.

## Overdue check returns 2

This is an operational alert, not a command failure. Review `overdueSystems` and `overdueRunning`, confirm the upstream backup platform result, and register/import the latest run. Escalate according to system criticality and the organization's recovery objectives.

## Database backup and restore

This project does not back up itself automatically. A local manual dump can be created with:

```bash
docker compose exec -T db pg_dump -U backup_catalog -d backup_catalog -Fc > backup-catalog.dump
```

Test restore procedures in a separate database. Never overwrite production state without an approved change and a verified dump.

## Safe shutdown

Use `docker compose down` to stop containers while retaining the named volume. `docker compose down --volumes` permanently deletes local PostgreSQL data and is intended only for deliberate development resets.
