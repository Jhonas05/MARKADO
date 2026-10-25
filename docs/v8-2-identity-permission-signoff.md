# MARKADO v8.2 remaining identity and permission sign-off

**Prepared:** 25 October 2026

**Status:** Decision-ready proposals only. ADR-008 is approved only for the
institutional-email and trusted roster-match boundaries it states.

> **Historical worksheet note (25 October 2026):** ADR-009 subsequently approved
> Student self-registration as the normal V1 direction, and ADR-010 subsequently
> approved bounded automatic Student-only activation after all approved checks.
> The token, delivery, recovery, roster-authority, D15, D19, and D23 proposals
> below remain unapproved. This note preserves the worksheet's original context.

## D13/D14 choices still required

| Topic | Recommended V1 rule | Status |
|---|---|---|
| Account issuance | Invitation or institution-managed provisioning until a public workflow is separately approved | PROPOSED |
| Roster authority | Named registrar/SIS owner imports and corrects records; define format and freshness | PROPOSED |
| Student-number normalization | Preserve issued value, including leading zeros; approve any canonical comparison rule explicitly | PROPOSED |
| Mailbox verification | Single-use random token, hash at rest, exact expiry, replay rejection, generic responses, resend throttling | PROPOSED |
| Token lifetime and mail service | Select a duration and approved institutional delivery service | OPEN |
| Password recovery | Verified institutional mailbox only; successful recovery revokes other sessions and remember tokens | PROPOSED |
| Lost mailbox | Staff-assisted case with separately authorized identity evidence; no plaintext password handling | PROPOSED |
| Duplicate/disputed accounts | Manual review; never auto-merge accounts, attempts, or grades | PROPOSED |
| Activation | Separate named authority after all evidence passes; verification never auto-activates | OPEN / D15 |

Minimum acceptance tests include hashed token storage, exact expiry, one-time
use, enumeration-safe responses, rate limiting, recovery session revocation,
and dispute handling that preserves every academic record. Verification-email
issuance and recovery mutations remain disabled until these values and actors
are approved.

## D15 named-permission proposal

No permission below is granted by the `academic_administrator` role alone.

| Proposed permission | Narrow purpose | Required scope/audit |
|---|---|---|
| `roster.read` | Read minimum roster identity | Assigned school/section and purpose |
| `roster.manage` | Import or correct roster state | Authorized source, reason, before/after |
| `identity.verify` | Confirm account-to-roster evidence | Named verifier, evidence reference, outcome |
| `section.assign` | Manage section membership | Section scope, actor, timestamp |
| `account.status.manage` | Activate or deactivate an account | Reason; no self-approval |
| `account.privilege.manage` | Grant or revoke privileged roles/permissions | Dual approval; no self-escalation |
| `audit.read` | Read approved privacy-safe audit projections | Purpose and resource scope |

Professors receive none of these automatically. Privileged mutation routes
remain disabled until the permission assignment authority, separation of
duties, and retention rules are approved.

## D23 exceptional assessment proposal

Keep reset, accommodation, regrade, and appeal as separate disabled workflows.
Do not create a general override permission. If approved later, every action
must require a named actor, separate authorizer where applicable, reason/case
reference, immutable original attempt/result/snapshot, privacy-safe before and
after state, and retry idempotency. An ordinary administrator must never bypass
ADR-005's global hard close.

## Activation blockers

- Approve the remaining D13/D14 actors, token parameters, recovery path, and
  duplicate-resolution procedure.
- Approve D15 permission assignment and audit/retention policy.
- Approve any D23 workflow separately; V1 recommendation is disabled.
- Supply authorized institutional domains and a governed roster source only in
  protected server configuration.
- Complete staging migration/restore, HTTPS/session configuration, mail
  delivery, monitoring, responsive UI, keyboard, and screen-reader checks.
- Obtain separate authorization before enabling registration, assessment
  routes, expiration processing, workers, or deployment.
