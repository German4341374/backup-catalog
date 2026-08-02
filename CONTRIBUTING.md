# Contributing

## Workflow

1. Create a focused branch from `main`.
2. Copy `.env.example` to the ignored `.env` file.
3. Keep business rules in application services or validators and database invariants in a new migration.
4. Add or update tests.
5. Run `composer validate --strict`, `composer lint`, `composer analyse`, and `composer test`.
6. Open a pull request using the repository template.

Do not edit an applied migration. Add the next ordered `VNNN__description.sql` file instead. Never commit credentials, `.env`, dumps, storage exports, or real backup error messages.

## Commit style

Use [Conventional Commits](https://www.conventionalcommits.org/):

- `feat: add retention policy filter`
- `fix: reject mismatched backup job mapping`
- `test: cover stalled backup threshold`
- `docs: clarify scheduler exit codes`

Keep pull requests small enough to review and explain operational or security trade-offs in the description.
