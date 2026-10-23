# Phase 5E assessment-engine implementation contract

**Updated:** 23 October 2026

**Status:** D07 hard close is approved by ADR-005. The implementation seams in
this branch remain dormant; this document does not activate the assessment
engine or approve D04-D06, D08 exceptions, D09, scoring, or release details.

## Approved D07 boundary

- Permit Start only while authoritative server time is strictly before
  `quiz_closes_at`.
- Do not require the complete session duration to fit before close.
- Persist `quiz_closes_at`, any separately approved session deadline, and their
  minimum as `effective_deadline_at` once at Start.
- Cap any question deadline by `effective_deadline_at`.
- At close, atomically submit every active attempt using the last acknowledged
  answers; unanswered items remain unanswered.
- Preserve the scheduled close as the logical submission timestamp even when a
  request or worker observes it later.
- Treat finalization and retries as idempotent under a row lock or equivalent
  conditional terminal transition.

## Prepared code contract

`HardCloseDeadlineGuard` accepts only an injected `AuthoritativeClock`. It
decides the strict Start boundary, calculates the approved minimum deadline,
caps question deadlines, and detects a reached deadline. Browser-reported time
is absent from its API.

`AttemptFinalizationCoordinator` serializes student-submit and quiz-close
transitions, preserves an existing terminal outcome, and returns a stable
receipt. It has no route, scheduler, interface binding, or enabled capability.

The additive migration prepares nullable published-revision and attempt
deadline/finalization fields. PostgreSQL rejects an effective attempt deadline
that is not exactly the minimum of its persisted close and optional session
deadline. The Laravel PostgreSQL connection is explicitly pinned to UTC;
Asia/Manila remains the display timezone.

## Acceptance traceability

| ID | D07 scenario | Expected evidence |
|---|---|---|
| D07-01 | Start immediately before close | Allowed using server time |
| D07-02 | Start exactly at close | Denied |
| D07-03 | Start after close | Denied |
| D07-04 | 09:50 start, 20-minute session, 10:00 close | Effective deadline is 10:00 |
| D07-05 | Unanswered items at close | Persisted answers retained; other items remain unanswered |
| D07-06 | Student Submit and close finalization race | Both row-lock serialization orders produce one terminal attempt |
| D07-07 | Duplicate finalization | Same timestamps/reason returned; no duplicate record |
| D07-08 | Browser clock differs | No effect on Start/deadline/finalization decision |
| D07-09 | Worker processes after close | Logical submission remains the scheduled close |
| D07-10 | Reconnect after close | Same terminal attempt and persisted deadline returned |

## Still blocked

- **D04:** whether and how both timer types coexist beyond the D07 close cap.
- **D05:** per-question expiration navigation and answer acceptance.
- **D06:** what a session-duration deadline does before global close.
- **D08:** retry/reset/accommodation and exceptional resume rules.
- **D09:** late saves, grace, disconnection, and outage recovery.
- **D10 remainder:** opening-boundary and other schedule semantics; all display
  remains Asia/Manila.

Until those prerequisites are approved, capabilities remain disabled and no
production attempt, answer, submission, timer, or scheduler endpoint is added.
