# Repository guidance

- Keep all source, comments, documentation, and commits in English.
- Preserve the layered boundaries under `src/`; controllers should delegate business rules to services and validators.
- Use prepared PDO statements for every value supplied outside the repository.
- Never edit an applied migration; add a new ordered migration.
- Never add real system names, people, credentials, backup paths, or operational logs.
- Run `composer lint`, `composer analyse`, and `composer test` before proposing a change.
- Use Conventional Commits and keep dependency versions intentional.
