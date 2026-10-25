# MARKADO v8 account security and activation readiness

**Prepared:** 24 October 2026

**Status:** ADR-008 now approves only the institutional-email and trusted
roster-match boundaries. All remaining recommendations in this document are
still proposed unless another dated ADR says otherwise.

**Applies to:** D13, D14, D15, D23, account security, and later controlled assessment activation.

## Guardrails

- ADR-003 through ADR-007 remain authoritative for their exact approved scope.
- Student number and email are required for future registration, but registration,
  verification, recovery, privileged provisioning, and exceptional assessment
  mutations remain disabled.
- Except for ADR-008's exact D13/D14 boundaries, the recommendations below are
  **PROPOSED**, not **APPROVED**. They cannot be
  used to enable a route, worker, or administrative mutation before a dated ADR
  records the product owner and academic-policy approver's decision.
- `MARKADO_ASSESSMENT_ROUTES_ENABLED` and
  `MARKADO_ASSESSMENT_EXPIRATION_ENABLED` remain `false` outside isolated tests.

## Current security baseline

The current implementation uses Laravel session authentication and accepts only
email and password at login. The server-stored role selects the destination;
client-supplied role or account-status values are ignored. Login is limited to
five failed attempts per normalized email and IP address per minute and returns
a generic failure message.

Protected Student, Professor, and Administrator workspaces recheck that the
account is active and has an assigned role on every request. A transition to
pending or disabled logs the current session out on its next protected request,
invalidates the session, and regenerates the CSRF token. Resource policies also
require an active account before allowing quiz or revision access.

Public registration and password-recovery pages are informational only; no
corresponding mutation routes exist. Quiz, attempt, and result access remains
resource-scoped. The shared Inertia authentication projection is limited to
user ID, display name, server role, and account status; email is not globally
shared. More sensitive identity fields are returned only by a page with a
specific authorized need.

New database accounts default to `pending`; an explicitly authorized
provisioning operation must assign `active`. The corrective migration changes
only the default and does not rewrite an existing account's status.

Production-like activation must additionally set secure HTTPS cookies, disable
debug output, protect the application key, terminate TLS, define session
retention/revocation, and verify proxy/cookie configuration. Local
`SESSION_SECURE_COOKIE=false` is not a production approval.

## V8 delivery verification boundary

Terminal-based security, database, test, lint, build, dependency-audit, and
source-scan checks are in scope for this delivery. Rendered desktop/mobile,
light/dark, keyboard, and screen-reader QA are **BLOCKED / NOT VERIFIED** because
the available browser session could not safely expose a trusted current URL.
No browser automation or alternate browser-control method was used to bypass
that safety boundary. These checks remain required before staging activation.

## D13 — student-number authority and roster lifecycle

**APPROVED by ADR-008:** the official student number must match the same
authoritative roster identity as the verified institutional email. A number by
itself proves neither ownership nor eligibility.

**Remaining PROPOSED recommendation**

1. The school registrar or SIS is the authoritative issuer of student numbers;
   MARKADO never invents or self-verifies one.
2. Store an immutable internal account identifier separately from the normalized
   student number. In the approved single-school V1, the normalized student
   number is unique across the school, including inactive records.
3. Only a named `identity.verify` permission assigned to registrar-authorized
   staff may verify or correct the number-to-person mapping. Professors may see
   the minimum roster identity required for their assigned sections, but cannot
   verify identities.
4. Use explicit roster states: pending import, verified active, inactive, and
   archived. A student is assessment-eligible only when the existing ADR-004
   eligibility rules and the verified-active identity state both pass.
5. Concurrent duplicates are rejected by a database constraint. Possible
   duplicates enter manual review; records, attempts, and grades are never
   silently merged, reassigned, or deleted.
6. Term changes, transfers, withdrawals, and graduation preserve historical
   attempt ownership and append an auditable roster transition.

**Trade-offs:** SIS/registrar authority reduces impersonation and duplicates,
but requires a reliable import/integration owner and a supported exception
queue. Retaining inactive identifiers prevents accidental reuse but requires an
approved retention policy under D19.

**Minimum acceptance tests:** normalized duplicate and concurrent-create
rejection; unverified/inactive denial; authorized correction with before/after
audit; unauthorized Professor/Admin denial; term rollover preserving attempts;
ambiguous duplicate quarantined without record merge.

## D14 — email, verification, pending behavior, and recovery

**APPROVED by ADR-008:** V1 uses school-issued institutional email only; the
mailbox must be verified and must match the same trusted roster identity as the
student number. Personal mailboxes and domain-only activation are rejected.

**Remaining PROPOSED recommendation**

1. V1 login and recovery use a school-controlled institutional email from an
   explicit domain allowlist. Do not accept a personal recovery address until
   its purpose, retention, and lawful basis are approved.
2. Verify possession with a high-entropy single-use token stored only as a hash
   and expiring after a separately approved lifetime. Resend and verify
   endpoints use generic responses, rate limits, and audit events that never
   reveal whether an account exists. The lifetime and delivery service remain
   OPEN.
3. Pending accounts may view only the pending-information screen. They cannot
   enter role workspaces, preflight a quiz, or create/resume an attempt.
4. Password recovery is permitted only through the verified institutional
   mailbox. A successful reset revokes other sessions and remember tokens,
   rotates the recovery state, and records a privacy-safe audit event.
5. A student who lost access to the institutional mailbox uses a staff-managed
   identity-recovery case. Support staff do not see or set a plaintext password.
6. Duplicate account recovery never auto-merges academic records. A separately
   authorized identity resolver records the evidence, actor, reason, and chosen
   canonical account; migration of academic records requires separate D23
   authority.

**Trade-offs:** institutional email is easier to govern and recover securely,
but excludes students before mailbox provisioning and needs a staffed recovery
path. A shorter token lifetime limits exposure but creates more support load;
the exact value requires separate approval.

**Minimum acceptance tests:** allowed/disallowed domain; hashed token at rest;
single use and exact expiry; generic unknown-account response; resend/recovery
rate limiting; pending-account denial; session revocation after reset; lost
mailbox path creates a case without mutation; duplicate recovery preserves all
records and audit evidence.

## D15 — named permissions and account administration

**PROPOSED recommendation**

Role labels provide a default permission set but every sensitive operation is
authorized by actor, action, and resource scope.

| Capability | Student | Professor | Administrator |
|---|---|---|---|
| Own profile and sessions | Read minimum own data; change approved credentials | Same | Same |
| Quiz/attempt access | Eligible own attempt and own released result | Own quizzes, assigned rosters, own-quiz results | None by default |
| Quiz authoring/publication | None | Own resources only | None by default |
| Roster/section read | Own membership only | Assigned sections, minimum identity fields | `roster.read` by school scope |
| Roster/section mutation | None | None | Separate `roster.manage` and `section.assign` grants |
| Identity verification | None | None | Separate `identity.verify` grant |
| Account activation/deactivation | None | None | Separate `account.status.manage` grant; cannot grant own privilege |
| Role or privileged-account grant | None | None | Separate dual-approved `account.privilege.manage` grant |
| Answer keys, answers, or grades | Own released projection only | Own quiz scope | None by default |
| Impersonation | None | None | Not offered in V1 |

Every privileged read or mutation records actor, target, permission used,
reason/case reference, timestamp, outcome, and privacy-safe before/after state.
Audit records are append-only to application actors and must not contain
passwords, tokens, answer text, or full answer keys. Activation, deactivation,
role grants, and section/roster changes require a reason. Privileged account
creation and self-escalation remain disabled until the exact provisioning and
separation-of-duties rules are approved.

**Trade-offs:** named permissions avoid a blanket super-admin and reduce grade
exposure, but require permission-assignment operations, periodic access review,
and clear ownership when a staff member changes roles.

**Minimum acceptance tests:** deny-by-default matrix; cross-school/resource
denial; Professor cannot mutate roster or accounts; Administrator without a
named grant cannot access the action; self-escalation denied; deactivation
revokes an existing session; before/after audit completeness; audit content
allowlist; revoked permission takes effect on the next request.

## D23 — exceptional reset, accommodation, regrade, and appeal

**PROPOSED recommendation**

1. Treat reset, accommodation, regrade, and appeal as four separate permissions
   and workflows. No general `override` permission exists.
2. Every request requires a named actor, affected student/quiz/attempt, reason,
   case or authorization reference, approving actor, decision timestamp, and
   before/after state. The requester cannot be the sole approver of their own
   exceptional action.
3. An attempt reset never deletes or rewrites the original attempt. It creates
   an append-only authorization and a successor-attempt lineage. Whether V1 may
   have that successor is still **BLOCKED** until explicitly approved.
4. Accommodations are recorded before Start and become immutable inputs to the
   attempt deadline calculation. They never extend beyond the global quiz close
   unless a later ADR explicitly creates an approved exception to ADR-005.
5. Regrading creates a versioned scoring decision and immutable adjustment; it
   does not overwrite the original answers, snapshot, score, or first result.
   Scope, batch behavior, already-released-result handling, and student notice
   all require explicit approval.
6. Appeals use a case state machine and a read-minimized reviewer view. Final
   disposition, evidence retention, and notice requirements follow approved
   school policy; no prototype button may mutate a grade.

**Trade-offs:** append-only lineage and dual authorization protect assessment
integrity and appeals, but increase operational work and storage. A global-close
cap maintains equal scheduling semantics but may require a separately approved
quiz window for an accommodation.

**Minimum acceptance tests:** permission separation; requester/approver
separation; reason/case required; immutable original attempt and result;
successor uniqueness; accommodation applied only before Start; ADR-005 cap
preserved; deterministic versioned regrade; released-result handling denied
until approved; complete audit and student-notice state; retry idempotency.

## Later staging-activation checklist

- [ ] D13, D14, D15, and D23 selections are recorded in dated ADRs with named
  approvers; unresolved options remain disabled.
- [ ] School privacy, retention, incident, and accessibility owners accept the
  applicable D19/D21 controls and student-facing wording.
- [ ] Production environment uses HTTPS, secure and HttpOnly cookies, an
  approved SameSite policy, `APP_DEBUG=false`, protected keys/secrets, and a
  trusted-proxy configuration test.
- [ ] Provisioning creates least-privilege named grants; no UI role selector or
  request payload can elevate an account.
- [ ] Migrations, PostgreSQL constraints, backup, and restore are rehearsed on a
  disposable staging database with synthetic data.
- [ ] Authentication, CSRF, login/recovery throttling, session revocation,
  cross-role/resource authorization, and PII projections pass security tests.
- [ ] CampusNav Student, Professor, and Administrator journeys pass supported
  desktop/mobile, keyboard, contrast, focus, and session-expiry checks.
- [ ] Attempt boundaries, answer privacy, idempotent saves/submission,
  concurrency, scoring, manual release, and scheduler failover pass on the exact
  candidate commit.
- [ ] A single monitored scheduler leader is configured; request-time deadline
  enforcement remains authoritative if it is delayed.
- [ ] Backups are encrypted, access-controlled, and restored successfully; the
  recovery objective, retention, and restore owner are recorded.
- [ ] Synthetic test accounts and artifacts are removed before activation;
  logs and audit streams contain no credentials, answer content, or keys.
- [ ] Enable assessment routes first in staging. Enable expiration processing
  separately only after route QA, monitoring, and rollback rehearsal pass.
- [ ] Production deployment and live-exam activation require a separate,
  explicit authorization.

## Approval record to complete

For each decision, record the selected option, rejected alternatives, product
owner, academic-policy approver, privacy/security reviewer, approval date,
effective version, student/staff wording, acceptance evidence, rollback path,
and revisit trigger. Until then, every recommendation in this document remains
**PROPOSED**.

## Sources

- `MARKADO_Context_Pack/decisions/ADR-003-phase-5b-approved-v1-boundaries.md`
- `MARKADO_Context_Pack/decisions/ADR-004-v1-publication-and-eligibility.md`
- `MARKADO_Context_Pack/decisions/ADR-005-v1-hard-close-auto-submission.md`
- `MARKADO_Context_Pack/decisions/ADR-006-v1-dual-timers-and-attempt-policy.md`
- `MARKADO_Context_Pack/decisions/ADR-007-v1-final-question-scoring-and-result-release.md`
- `MARKADO_Context_Pack/decisions/ADR-008-v1-institutional-student-onboarding.md`
- `MARKADO_Context_Pack/context/03_roles-and-permissions.md`
- `MARKADO_Context_Pack/context/06_student-registration-and-access.md`
- `MARKADO_Context_Pack/context/14_security-privacy-and-risk.md`
- `MARKADO_Context_Pack/context/18_open-decisions.md`
- `docs/phase-5c-assessment-engine-readiness.md`
