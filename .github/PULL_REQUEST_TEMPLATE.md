## Summary

Describe the operational problem and the implemented change.

## Verification

- [ ] `composer validate --strict`
- [ ] `composer lint`
- [ ] `composer analyse`
- [ ] `composer test`
- [ ] Container configuration/build checked when applicable

List the exact commands and results. Mark anything not run and explain why.

## Security and operations

- [ ] No credentials, real logs, personal data, or database state are included.
- [ ] New SQL values use prepared statements.
- [ ] Schema changes use a new migration.
- [ ] Documentation and rollback notes are updated where needed.
