# MARKADO v8.6 — Institutional email verification operating sign-off

**Prepared:** 25 October 2026 (Asia/Manila)

**Policy source:** ADR-011

**Implementation state:** default-off foundation; no public mutation route or real delivery

## Approved and implemented

- Random 256-bit one-time secret delivered only through an internal delivery contract.
- Keyed SHA-256 token hash and institutional-email fingerprint at rest; no duplicate email field.
- Exact validity condition `server_now < issued_at + 30 minutes`.
- Exact resend condition `server_now >= prior issued_at + 60 seconds`.
- Required rolling-hour limit with race-safe account serialization.
- One successful consume under concurrency, replay denial, email-change invalidation, and privacy-safe audit outcomes.
- Mailbox proof remains separate from trusted roster match, Student-only activation, and quiz eligibility.
- Public registration, verification, resend, activation, recovery, roster, and privileged-account mutations remain absent.

## PROPOSED, not approved

- Five issues per rolling hour as a candidate operational value. The application intentionally has no production default.
- Network-aware monitoring should combine account/mailbox signals with risk-sensitive network evidence; it must not use a school-wide IP-only lockout.

## OPEN before staging or live delivery

- Exact hourly maximum, monitoring/alert rules, incident response, and authorized security owner.
- Approved institutional domain list, mail provider, sender/DNS, application URL origin, transport credentials, and delivery observability.
- Registrar/SIS authority and freshness, pending-claim timing, duplicate/dispute handling, recovery, identity correction, and support ownership.
- D15 privileged grants and separation of duties; D19 challenge/audit retention; accessibility and threat-review sign-off.
- Separate authorization for protected migration, public route activation, deployment, or assessment activation.

## Acceptance trace

`InstitutionalEmailVerificationTest` covers lifecycle, exact boundaries, configurable limits, generic projection, delivery isolation, rollback, email change, activation separation, feature flags, and route absence. `PostgreSqlAssessmentConcurrencyTest` covers concurrent consume and issue behavior in an isolated PostgreSQL schema. Operational mail and rendered UI evidence must be collected only after a provider and safe browser/staging environment are approved.
