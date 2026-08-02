# Security policy

## Supported versions

Security fixes are applied to the latest commit on `main`. This portfolio project does not maintain older release branches.

## Reporting a vulnerability

Do not open a public issue for a suspected vulnerability. Use GitHub's private vulnerability reporting feature on the Security tab. Include affected routes or commands, reproduction steps using fake data, impact, and a suggested mitigation when possible. Do not include credentials, production logs, database dumps, or personal data.

## Deployment boundary

Backup Catalog has no built-in authentication. It must not be exposed directly to the public internet. Put it on a restricted administrative network or behind an identity-aware reverse proxy with TLS, access logging, request limits, and organization-managed authorization.

Environment variables are appropriate for this local demonstration. Production secrets should come from an orchestrator secret store and be rotated according to organizational policy.
