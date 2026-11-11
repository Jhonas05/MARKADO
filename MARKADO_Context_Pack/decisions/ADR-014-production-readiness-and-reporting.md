# ADR-014 — Production identity readiness and deterministic result reporting

- Status: APPROVED implementation contract; production activation remains conditional
- Recorded: 11 November 2026, Asia/Manila
- Authority: product owner's full live MVP implementation and production release request
- Related: ADR-004–011, ADR-013, `docs/production-release-readiness.md`

The product owner authorizes the production-capable Student onboarding path and Professor reporting features. This authority does not supply an institutional domain, an authoritative roster feed, mail credentials, privacy/retention ownership, or operational evidence. Those inputs must never be invented.

## Production Student registration

Public Student registration may become available only when its dedicated public-registration switch and all existing identity flags are explicitly enabled and the runtime verifies every condition below:

1. Laravel is running in `production` with PostgreSQL and a non-loopback HTTPS public origin.
2. An approved HTTPS transactional-email adapter (or a verified non-loopback SMTP transport) and verified sender are configured. Log, array, loopback, missing-secret, or otherwise non-delivering transports are rejected.
3. The institution supplies at least one exact institutional email domain.
4. The institution names its real authoritative roster source and approves a maximum roster age from 1 through 168 hours.
5. Each matching roster row carries that exact source identifier and a fresh synchronization timestamp. Missing, mismatched, or stale provenance fails closed.
6. The existing exact student-number/mailbox match, active and verified roster status, independent mailbox proof, Student-only role boundary, generic responses, hash-at-rest token, 30-minute lifetime, 60-second cooldown, five-per-hour ceiling, and atomic activation checks all remain in force.

The new production gate extends ADR-013; it does not weaken the guarded local demonstration. Staff registration, roster creation through the public request, role selection, and mailbox-only activation remain prohibited.

## Result reporting and exports

An owning active Professor may view one quiz's attempts in either deterministic score order or student-name order and export the same dataset as PDF or DOCX. Score order is percentage high-to-low; ties use student name and then student number as stable secondary keys. This presentation order is not an academic rank and does not create rank numbers, alter grades, release results, or expose answer keys.

Exports are private, no-store responses, rate-limited, owner-authorized, and recorded as assessment audit events with only format, order, and record count. Student result disclosure remains limited to earned points and maximum points after an explicit Professor release.

## Operational consequence

Code, migrations, tests, and default-off deployment may proceed independently. Staging activation requires a separate database and synthetic roster/mail configuration. Production activation and live assessment delivery remain blocked until the missing institutional inputs, backup/restore evidence, scheduler ownership, and browser acceptance evidence are supplied and verified.
