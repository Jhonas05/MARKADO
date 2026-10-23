# Phase 5C assessment-engine readiness checklist

**Status:** D16 publication, D22 eligibility, D07 hard close, and the ADR-006
D04-D06/D08-D10 attempt rules are approved. This checklist is not by itself
authorization to enable unverified student access, scoring, registration,
roster mutation, privileged overrides, or grade changes.

**Prepared:** 22 October 2026; integration references updated 23 October 2026

**Implementation base:** integrated `main` at
`84ff7eba418e600d816e0c092063516569b6300b`

## Purpose

This checklist defines the decisions, engineering safeguards, and verification
evidence required before MARKADO can activate a database-backed student
assessment engine. It preserves the product-owner boundaries in ADR-003 and
must not be treated as approval for any still-open academic policy.

## Current verified foundation

- [x] Laravel, PostgreSQL, React, and Inertia.js foundation is present.
- [x] Instructor-owned quiz drafts use server-side validation and resource
  policies.
- [x] V1 authoring accepts only single-answer multiple choice and True/False.
- [x] Stable UUID relationships exist for quizzes, questions, and choices.
- [x] The schema reserves one attempt per student per quiz.
- [x] Attempt-question snapshots are immutable at the model boundary.
- [x] Saved answers are constrained to a snapshot belonging to the same
  attempt.
- [x] Manual result release state is represented without activating release or
  scoring behavior.
- [x] Quiz codes and links are reserved without exposing a public attempt route.
- [x] D16 immutable publication revisions, explicit draft-from-published, and
  authorized unpublish are implemented and locally tested.
- [x] D22 trusted direct/section assignment evaluation is connected to preflight
  with deny-by-default behavior and no assignment mutation routes.
- [x] D07 defines a strict hard close: starts are allowed only before close and
  active attempts are automatically submitted at close without extending time.
- [x] ADR-006 defines dual-timer precedence, question/session expiry, ordinary
  resume, connectivity acknowledgement, and open/close boundary semantics.
- [x] Student attempts, registration, scoring, timers, and privileged roster
  mutation remain disabled.

## Product decisions required before implementation

Each decision needs a named approver, approval date, selected behavior, exact
boundary semantics, student-facing wording, and acceptance-test examples.

| Decision | Required approval | Status |
|---|---|---|
| D04 | Both timers may coexist; earliest persisted question/session/global deadline wins | APPROVED â€” ADR-006 |
| D05 | Lock, preserve acknowledged answer, auto-advance, no backward navigation | APPROVED WITH FINAL-QUESTION EDGE OPEN â€” ADR-006 |
| D06 | Whole-session expiry atomically finalizes acknowledged answers and returns a stable receipt | APPROVED â€” ADR-006 |
| D07 | Hard close, strict no-start boundary, and automatic submission of acknowledged answers | APPROVED — ADR-005 |
| D08 | Same attempt resumes across refresh/sign-in/reconnect/tab/device; student reset denied; staff exceptions open | APPROVED WITH STAFF EXCEPTIONS OPEN â€” ADR-006 |
| D09 | Timers continue; acknowledgement-only saves; no automatic grace; idempotent/versioned reconciliation; institutional remedies open | APPROVED WITH INSTITUTIONAL REMEDIES OPEN â€” ADR-006 |
| D10 | Inclusive open, exclusive close, UTC persistence, Asia/Manila display, server clock authority | APPROVED â€” ADR-006 |
| D11 | Released feedback contents and answer-key visibility | PARTIAL |
| D12 | Points, final score, rounding, ties, ranking, and incomplete-grade rules | OPEN |
| D13–D14 | Student-number/email issuance, verification, uniqueness, recovery, and duplicate handling | PARTIAL |
| D15 | Exact administrator permission matrix and audit requirements | PARTIAL |
| D16 | Immutable publication, separate draft revisions, and unpublish boundaries | APPROVED — ADR-004 |
| D22 | Verified school-managed direct/section eligibility with deny-by-default behavior | APPROVED — ADR-004 |
| D23 | Regrade, override, appeal, and adjustment authority | OPEN |

Until the applicable rows are approved, do not infer defaults from prototype
copy, mock data, framework conventions, or this document.

## Entry gates for Phase 5C implementation

### Policy and UX contract

- [ ] Record the approved attempt state machine and every permitted transition.
- [x] Define ordinary start, save, and non-close expiry rules in ADR-006. The
  D05 final-question edge remains disabled pending a separate decision.
- [x] Define what `resume` means after refresh, sign-out, reconnect, and use of a
  second tab or device.
- [x] Define the authoritative save acknowledgement and unsaved-answer warning.
- [ ] Approve student instructions for timing, navigation, submission, release,
  and connectivity limitations before the Start action is enabled.
- [ ] Decide whether unanswered questions may be submitted and what confirmation
  is required.
- [ ] Define accessible warning thresholds without disruptive flashing or
  excessive screen-reader announcements.

### Identity, access, and privacy

- [x] Approve the school-managed eligibility source used at quiz preflight and
  future attempt start. Exact D13-D15 verification/mutation authority remains
  open and no mutation UI is enabled.
- [ ] Define administrator, instructor, and support access at resource level;
  role names alone are insufficient.
- [ ] Define access-code entropy, rotation/revocation, lookup throttling, and
  safe error messages that do not reveal quiz existence.
- [ ] Confirm every attempt, answer, result, and export endpoint has a Laravel
  policy or equivalent resource-scope authorization check.
- [ ] Confirm Inertia shared props, page props, validation errors, logs, and
  browser history cannot expose answer keys or unreleased results.
- [ ] Approve audit events for start, save rejection, submit, expiry, release,
  regrade, access change, and privileged record access.
- [ ] Confirm development and test fixtures contain synthetic identities only.

### Data and transaction design

- [ ] Finalize attempt statuses and legal transitions without overloading quiz
  publication status.
- [x] Approve authoritative server timestamps in UTC and display them in
  Asia/Manila; never accept browser time as authority.
- [x] Approve persisting deadline values once without regenerating them on
  refresh or reconnect.
- [x] Choose and document the immutable quiz revision/snapshot creation point
  in ADR-004.
- [ ] Persist randomized question and choice order by stable IDs, never display
  letters or array indexes.
- [ ] Validate every submitted choice against the attempt's immutable snapshot.
- [ ] Define stable idempotency keys for Start, answer saves, and Submit.
- [ ] Make terminal submit/expire transitions atomic under PostgreSQL locking or
  an equivalent concurrency control.
- [ ] Preserve submitted answers and original calculated results; adjustments
  must be separate, attributed audit records.
- [ ] Review constraints and indexes with PostgreSQL query plans for join,
  resume, monitoring, and result-release paths.
- [ ] Use additive, reversible migrations and rehearse backup/restore before any
  production-like activation.

### Server and client boundaries

- [x] Approve Laravel as the authority for authentication, eligibility, availability, deadlines,
  answer acceptance, terminal state, and result visibility.
- [x] Approve React countdowns and client validations as display aids only.
- [ ] No correct-answer flag, rubric, foreign attempt data, or unreleased score
  reaches the student payload.
- [x] D07 requires that a delayed close worker cannot extend the persisted
  global close; request-time enforcement remains authoritative.
- [x] Approve refresh and reconnection rehydrating the server attempt, saved answers,
  fixed ordering, and unchanged deadlines.
- [x] Approve truthful `Saving`, `Saved`, `Connection Lost`, `Save Failed`,
  `Submitted`, and `Expired` states.

## Required automated verification

Run these tests against PostgreSQL, not an SQLite substitute for database
invariants.

- [ ] Duplicate or concurrent Start requests produce one student/quiz attempt.
- [ ] Start is rejected for unauthenticated, ineligible, unpublished, closed, or
  foreign resources without leaking protected data.
- [ ] Refresh/reconnect returns the same attempt, snapshot order, saved answers,
  and persisted deadlines.
- [ ] Answer-save retries are idempotent and cross-attempt question/choice IDs
  are rejected.
- [ ] A save racing a deadline or terminal transition cannot mutate locked work.
- [ ] Double Submit and submit/expire races persist exactly one terminal outcome
  and one stable receipt.
- [ ] Browser clock changes cannot extend time or make a late mutation valid.
- [ ] Exact open/close/deadline boundaries pass using a controllable server clock.
- [ ] Published-quiz edits cannot alter an already-created attempt snapshot.
- [ ] Randomization includes each eligible item once and preserves correctness
  through stable IDs.
- [ ] Forged scores, remaining-time values, statuses, ownership IDs, and release
  flags are ignored or rejected server-side.
- [ ] Students cannot retrieve another student's attempt, answers, or results.
- [ ] Instructors cannot retrieve another instructor's private quiz or its
  attempts/results.
- [ ] Unreleased results remain inaccessible through HTTP, Inertia props,
  browser history, and direct identifiers.
- [ ] Manual release is authorized, auditable, repeat-safe, and does not reveal
  unapproved feedback.
- [ ] Keyboard, screen-reader, desktop, and mobile attempt flows are checked.
- [ ] PHPUnit, Pint, ESLint, Vitest, production builds, and the full GitHub
  Actions workflow pass on the exact candidate commit.

## Suggested implementation slices after approval

1. **Join and preflight:** authenticated code/link resolution, publication and
   eligibility checks, instructions, and no implicit attempt creation.
2. **Atomic start and resume:** create one attempt plus immutable snapshot and
   persisted deadlines under the approved rules.
3. **Answer persistence:** server-authorized, idempotent saves with truthful
   React/Inertia feedback and reconnect recovery.
4. **Submission and expiry:** one atomic terminal transition with a stable
   receipt and deterministic race handling.
5. **Scoring and release:** implement only the approved D11/D12 rules, retain
   keys server-side, and gate student visibility behind professor release.
6. **Monitoring and operations:** scoped instructor status views, audit events,
   metrics without answer content, and recovery exercises.

Each slice requires its own threat review, PostgreSQL feature tests, frontend
regression coverage, and manual desktop/mobile verification. A later slice must
not be used to smuggle an unresolved policy into an earlier one.

## Pull-request integration gate

- [x] PR #1–#9 were integrated into `main` under separate approvals.
- [x] Phase 5D starts from verified PR #9 merge commit
  `84ff7eba418e600d816e0c092063516569b6300b`.
- [x] Existing feature branches and ancestry were preserved during integration.
- [x] The active local development checkout and ignored `.env` were preserved;
  Phase 5D work uses an isolated worktree.
- [ ] Repeat conflict, secret, personal-data, and unintended-diff review for the
  Phase 5D pull request before any future merge approval.
- [ ] Never force-push, rewrite the stack, or auto-merge a dependent PR.

The former PR #1–#9 dependency chain is complete. Phase 5D is delivered from
`feat/v1-publication-eligibility` to `main` and must not be merged without
separate authorization.

## Phase 5C exit criteria

- [ ] Every behavior implemented in Phase 5C maps to a recorded approved
  decision and acceptance test.
- [ ] No known critical identity, authorization, timer, submission, answer-key,
  or grade-integrity defect remains.
- [ ] PostgreSQL concurrency, boundary-time, idempotency, ownership, and snapshot
  tests pass on the exact delivered commit.
- [ ] Student prototype flows not activated by Phase 5C remain clearly labeled
  and cannot call production attempt endpoints.
- [ ] Manual desktop/mobile/accessibility evidence is recorded.
- [ ] Backup/restore and incident-response checks are completed before any
  production deployment.
- [ ] Delivery report includes branch, commit, PR, CI run, test evidence, approved
  decisions, deferred policies, and known risks.

## Source of truth

- `MARKADO_Context_Pack/decisions/ADR-003-phase-5b-approved-v1-boundaries.md`
- `MARKADO_Context_Pack/decisions/ADR-004-v1-publication-and-eligibility.md`
- `MARKADO_Context_Pack/decisions/ADR-005-v1-hard-close-auto-submission.md`
- `MARKADO_Context_Pack/decisions/ADR-006-v1-dual-timers-and-attempt-policy.md`
- `MARKADO_Context_Pack/context/04_user-journeys-and-states.md`
- `MARKADO_Context_Pack/context/07_timers-schedules-and-attempts.md`
- `MARKADO_Context_Pack/context/08_questions-randomization-and-scoring.md`
- `MARKADO_Context_Pack/context/14_security-privacy-and-risk.md`
- `MARKADO_Context_Pack/context/15_test-plan-and-QA.md`
- `MARKADO_Context_Pack/context/18_open-decisions.md`

When this checklist conflicts with an approved ADR, the approved ADR controls.
