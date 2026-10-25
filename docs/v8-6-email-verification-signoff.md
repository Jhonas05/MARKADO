# MARKADO v8.6 — Institutional email verification operating sign-off

**Prepared:** 25 October 2026 (Asia/Manila)

**Policy source:** ADR-011 and ADR-012

**Implementation state:** default-off foundation; no public mutation route or real delivery

## Approved and implemented

- Random 256-bit one-time secret delivered only through an internal delivery contract.
- Keyed SHA-256 token hash and institutional-email fingerprint at rest; no duplicate email field.
- Exact validity condition `server_now < issued_at + 30 minutes`.
- Exact resend condition `server_now >= prior issued_at + 60 seconds`.
- Maximum five initial/resend requests per rolling hour across the Student account and bound mailbox, with lower positive configuration allowed and invalid/above-five configuration failing closed.
- One successful consume under concurrency, replay denial, email-change invalidation, and privacy-safe audit outcomes.
- Mailbox proof remains separate from trusted roster match, Student-only activation, and quiz eligibility.
- Public registration, verification, resend, activation, recovery, roster, and privileged-account mutations remain absent.

## PROPOSED, not approved

- Network-aware monitoring should combine account/mailbox signals with risk-sensitive network evidence; it must not use a school-wide IP-only lockout.

## OPEN before staging or live delivery

- Monitoring/alert rules, incident response, and authorized security owner.
- Approved institutional domain list, mail provider, sender/DNS, application URL origin, transport credentials, and delivery observability.
- Registrar/SIS authority and freshness, pending-claim timing, duplicate/dispute handling, recovery, identity correction, and support ownership.
- D15 privileged grants and separation of duties; D19 challenge/audit retention; accessibility and threat-review sign-off.
- Separate authorization for protected migration, public route activation, deployment, or assessment activation.

## Acceptance trace

`InstitutionalEmailVerificationTest` covers lifecycle, exact boundaries, the five-request default and hard ceiling, lower configuration, fail-closed invalid configuration, failed/delayed delivery, mailbox sharing, generic projection, rollback, email change, activation separation, feature flags, and route absence. `PostgreSqlAssessmentConcurrencyTest` covers concurrent consume and account/mailbox fifth-versus-sixth issue behavior in an isolated PostgreSQL schema. Operational mail and rendered UI evidence must be collected only after a provider and safe browser/staging environment are approved.
