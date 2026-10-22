# ADR-003: Phase 5B approved V1 boundaries

**Status:** DECIDED by user

**Date:** 22 October 2026

**Decision scope:** The exact, limited portions of D02, D03, D08, D10, D11,
D13, D14, and D15 stated below. Unlisted sub-policies remain open.

## Context

Phase 5B begins real PostgreSQL-backed quiz authoring. The product owner approved
a narrow V1 foundation while explicitly withholding approval for student-attempt
activation, eligibility, deadline/expiry behavior, final scoring, and unrestricted
account creation.

## Approved decisions

1. MARKADO V1 is a **single-school** application. Multi-school tenancy is out of
   the current scope. Course/section eligibility rules remain open.
2. V1 authoring supports **single-answer multiple choice** and **True/False**.
   Other question formats, points, partial credit, and scoring rules remain open.
3. The persistence model must enforce **one attempt per student per quiz** and
   support resuming saved work. Attempt activation, retry authority, closing,
   expiry, and outage behavior remain open.
4. User-facing date/time values use **Asia/Manila** as the display timezone.
   Schedule and expiry semantics remain open.
5. Results require a **professor-controlled manual release**. Score calculation,
   feedback contents, answer-key visibility, ranking, and release batching remain
   open.
6. Future student registration requires **student number and email**. Issuance,
   verification, uniqueness/recovery, and self-service registration rules remain
   open; this decision does not enable registration.
7. Administrators have **limited, permission-based access**. No blanket quiz,
   answer-key, grading, account-creation, or impersonation access is inferred.

## Consequences and risks

- Quiz, question, choice, attempt, snapshot, and saved-answer tables may be built
  now around these stable constraints.
- Instructor draft authoring must use resource ownership policies. A role check
  alone is insufficient, and administrators receive no implicit override.
- Access codes and links may be reserved, but no public attempt route is enabled.
- Attempt snapshots are immutable once created and saved answers are constrained
  to a snapshot belonging to the same attempt.
- D04-D07, D09, D12, D16, D22, and D23 still block production attempt, timer,
  publishing, scoring, eligibility, and grade-change workflows.

## Acceptance and verification

- Feature tests prove instructors cannot read or mutate another instructor's
  private quiz.
- Validation accepts only the two approved formats and exactly one correct choice.
- Database tests prove one attempt per student/quiz and prevent cross-attempt
  answer/snapshot associations.
- No student attempt route, registration route, scoring service, or timer-expiry
  action is introduced by Phase 5B.

**Revisit** when the product owner explicitly resolves the remaining sub-policies.
