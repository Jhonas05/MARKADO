# ADR-004: V1 immutable publication and school-managed eligibility

**Status:** APPROVED by product owner

**Approval date:** 23 October 2026

**Decision scope:** D16 and D22 only, with the exclusions below.

## Context

MARKADO now has authenticated, privacy-safe quiz-code preflight and
instructor-owned draft authoring. The product owner approved the publication
snapshot and eligibility rules required to connect those foundations without
activating student attempts or deciding unresolved timing, scoring, identity
issuance, or administrator-permission policy.

## D16 approved decision

1. Publishing creates an immutable revision containing the approved quiz
   metadata, questions, choices, answer-key references, and applicable approved
   settings.
2. A published revision is never silently modified. Editing a published quiz
   creates a separate draft revision copied from the current published revision.
3. Draft changes do not alter the current published revision. A draft must pass
   validation and be explicitly published before it becomes current.
4. Existing attempts and immutable snapshots retain their revision and stable
   question/choice mapping when a later revision is published.
5. Unpublishing blocks new starts without rewriting existing attempts or
   snapshots.
6. Laravel ownership policies and PostgreSQL constraints protect revision
   selection, numbering, relationships, and published content.

Unpublishing does **not** decide what happens to an already-running attempt.
Automatic cancellation, submission, extension, or expiry remains blocked by
D07 and the related attempt-lifecycle decisions.

## D22 approved decision

1. MARKADO V1 uses verified, school-managed student records within one school.
2. A professor must assign a quiz through an active direct-student assignment or
   an active assigned section containing an active student membership.
3. A code or link is navigation, not authorization. Laravel evaluates the
   authenticated student against trusted server/database records.
4. Missing, inactive, stale, unverified, unauthorized, or unavailable
   eligibility information denies access by default with a non-revealing
   response.
5. The same eligibility gate must be rechecked when a real Start operation is
   eventually approved.
6. Privileged eligibility changes must be narrowly permissioned and auditable.

This decision does not establish who may issue or verify student numbers,
institutional email verification, recovery, or account correction. Until
D13-D15 resolve those authorities, no self-verification or roster-mutation UI is
enabled and test records are synthetic.

## Data and authorization consequences

- A quiz selects one current immutable published revision and may hold one
  separately editable draft revision.
- Attempts can reference the exact quiz revision; question snapshots can
  reference the exact revision question.
- School student records, sections, memberships, and direct/section quiz
  assignments store active state plus actor/timestamp audit metadata.
- Publishing, unpublishing, draft creation, and revision editing require
  instructor ownership at the resource level.
- Student preflight returns metadata from only the selected published revision
  after authentication, publication, and eligibility checks. It never returns
  questions, choices, correct-answer flags, access codes, or another student's
  records.
- Assignment mutation endpoints remain absent until D13-D15 grant exact
  authorities.

## Acceptance criteria

- A valid owner can publish a valid MCQ/True-False draft; invalid content and
  cross-instructor actions are rejected.
- Published metadata, questions, choices, and answer keys are immutable through
  all registered authoring routes.
- Draft-from-published and re-publication preserve older revisions and attempt
  mappings; repeated operations remain consistent under transaction locks and
  unique revision numbering.
- Unpublishing makes preflight unavailable without deleting history.
- Only a verified active student with an active direct or section assignment
  receives the safe preflight information projection.
- Invalid codes, unpublished quizzes, unverified records, wrong/inactive
  sections, missing assignments, and unavailable trusted sources share a safe
  denial contract and create no attempt.

## Delivery state

- **APPROVED:** D16 and D22 policies above, confirmed by the product owner on
  23 October 2026.
- **IMPLEMENTED:** Phase 5D code exists on
  feat/v1-publication-eligibility; it is not part of main until separately
  reviewed and merged.
- **TESTED:** Local PostgreSQL 17 verification passed 58 tests and 615
  assertions; SQLite regression passed 55 tests and 606 assertions with three
  PostgreSQL-only skips; Pint, ESLint, 14 Vitest tests, both production builds,
  dependency audits, and tracked-candidate secret scanning passed. Rendered
  desktop/mobile inspection was unavailable because the computer-use browser
  inventory exposed no usable surface, so visual fidelity is not claimed.
- **BLOCKED:** Real Start, timers, answer saving, submission, scoring, result
  release, roster mutation, and administrator mutation remain blocked by their
  unresolved decisions.

## Remaining dependencies

D04-D12 (except the limited existing V1 boundaries), D13-D15, D17-D21,
D23, D28, and D29 remain unresolved to the extent recorded in the decision
register. ADR-003 remains authoritative for its earlier approved boundaries.

**Revisit** if the school changes its eligibility source, needs multi-school
tenancy, approves privileged roster operations, or changes attempt behavior
during unpublish.

## Subsequent decision

ADR-005, approved 23 October 2026, now defines the D07 global hard-close rule.
This does not change ADR-004's publication or eligibility boundaries and does
not decide any other attempt behavior during an instructor-initiated unpublish.
