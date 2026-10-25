# MARKADO v8.5 — Remaining account-security sign-off

**Prepared:** 25 October 2026 (Asia/Manila)
**Repository baseline:** `main` `5be22c6ee1d2f09ed159c98011c062c636d87aa0`
**Status:** ADR-009 through ADR-011 directions are approved. Every item explicitly marked OPEN or PROPOSED still requires its named authority.

## Approved product-owner scope

- **D13/D14 / ADR-008:** school-issued institutional email only; verified mailbox plus exact same-record official student-number/email roster match.
- **D13/D14 / ADR-009:** Student self-registration is the normal V1 Student onboarding model.
- **D14 / ADR-010:** automatic Student-only activation after every independently approved identity, account-state, security, and anti-abuse check succeeds. No routine staff approval is required after all such checks pass.
- **D14 / ADR-011:** cryptographically random single-use hash-at-rest mailbox challenges; exact 30-minute validity; exact 60-second resend cooldown; required hourly limiting; generic public acknowledgements. Mailbox proof alone does not activate an account.
- Registration never edits the authoritative roster, grants staff privileges, proves quiz eligibility, or activates assessment routes.
- Missing, inactive, stale, unavailable, ambiguous, duplicate, disputed, or conflicting evidence fails closed.

## Decisions still required

| ID | Required decision | Recommended direction — NOT APPROVED | Needed authority |
| --- | --- | --- | --- |
| D13-1 | Authoritative roster issuer/corrector | Name the registrar/SIS owner and authorized service identities | Product owner + registrar |
| D13-2 | Source/import/freshness | Authenticated SIS/API or approved file import, integrity checks, and explicit freshness ceiling | Registrar + security/operations |
| D13-3 | Student-number canonicalization | Preserve leading zeros; approve any normalization in writing | Registrar |
| D14-1 | Institutional domains | Protected server-side exact allowlist, including alias/subdomain rules | School IT + product owner |
| D14-2 | Pending claim timing | Decide whether a pending user/claim is stored before or after mailbox proof | Product owner + security |
| D14-3 | Mail delivery operation | Approve SMTP/API provider, sender/DNS, application origin, monitoring, and staging evidence | School IT + security |
| D14-4 | Remaining abuse controls | Approve the numeric rolling-hour maximum plus network/account signals and monitoring; five/hour is PROPOSED only | Security + product owner |
| D14-5 | Password/lost-mailbox recovery | Institutional mailbox and separately governed staff-assisted exception | Registrar + IT + privacy/security |
| D13/D14-6 | Duplicate/disputed identity | Quarantine and authorized review; never auto-merge academic history | Registrar + academic/privacy |
| D15 | Staff permissions | Resource-scoped named grants, deny by default, accountable assignment | Academic administration + security |
| D19 | Retention/privacy | Purpose, access, and retention schedule for identity/audit evidence | School privacy owner |
| D23 | Exceptional assessment actions | Keep reset/accommodation/regrade/appeal disabled pending separate ADRs | Academic policy owner |

Proposed D15 permission names remain `roster.read`, `roster.manage`, `identity.verify`, `section.assign`, `account.status.manage`, `account.privilege.manage`, and `audit.read`. They are labels for future review, not granted capabilities.

## Implementation-ready boundary

`registration claim -> institutional domain check -> mailbox proof -> exact unlinked/owned trusted roster match -> current roster/account recheck -> approved security gate -> atomic Student-only link/activation -> separate quiz eligibility`

The repository implementation keeps public registration/recovery mutations absent and binds the operational activation-security gate to deny by default. Tests may substitute an allow decision only in isolated synthetic environments.

## Activation checklist before any public route

- Named roster authority/source, data contract, freshness, and correction lifecycle approved.
- Institutional domains configured privately and validated.
- Mail provider/origin, numeric hourly maximum, network monitoring, and incident handling approved and threat-reviewed. ADR-011 already fixes token, expiry, replay, cooldown, and generic-response semantics.
- Duplicate/dispute/mailbox-change/recovery procedures approved.
- D15 assignment/audit and D19 privacy/retention approved where operationally required.
- Isolated PostgreSQL migration and concurrency rehearsal, staging HTTPS/session/mail tests, monitoring, rollback, accessibility, and incident support complete.
- Separate authorization obtained to migrate a protected schema, enable public registration, activate assessments, or deploy.

## Operations hold

No public signup/verification/recovery mutation, live email, staff roster mutation, `markado.public` migration, assessment activation, worker activation, or deployment is authorized by ADR-009/ADR-010/ADR-011.
