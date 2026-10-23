# ADR-005: V1 hard close with automatic submission

**Status:** APPROVED by product owner

**Approval date:** 23 October 2026

**Decision scope:** D07 only. This ADR supersedes earlier D07 recommendations
that would deny a start when the full configured session duration could not fit
before the quiz close.

## Decision

1. An authenticated, eligible student may start a published quiz at any instant
   strictly before its persisted closing timestamp, subject to the other
   approved attempt preconditions.
2. The remaining availability window does not need to contain the full
   configured session duration.
3. A start at or after the exact closing timestamp is denied.
4. At the closing timestamp, every active attempt is automatically submitted.
   The submission preserves only answers already acknowledged and persisted by
   Laravel/PostgreSQL; unanswered questions stay unanswered.
5. The global close caps every attempt and active question deadline. It is never
   extended by refresh, another tab or device, browser-clock changes,
   reconnection, or a delayed worker.
6. Close finalization is atomic and idempotent. A student submission, request
   deadline check, scheduled job, or retry converges on one terminal attempt
   and cannot create duplicate results.
7. Request-time enforcement uses the authoritative server clock. A delayed
   worker records when it ran but preserves the scheduled close as the logical
   submission deadline.

When an approved whole-session duration exists:

`effective_attempt_deadline = MIN(started_at + configured_quiz_duration, quiz_closes_at)`

The global close also caps any separately calculated question deadline.

## Boundary example

For a quiz open from 09:00 to 10:00 Asia/Manila with a 20-minute configured
session, a student starting at 09:50 receives an effective deadline of 10:00.
The server automatically submits the attempt at 10:00. A start at exactly
10:00 is denied.

## Explicit exclusions

This ADR does **not** decide:

- D04 interaction between per-question and whole-session timers;
- D05 the separate behavior when a per-question timer expires;
- general D06 session-duration expiry behavior before the global close;
- D08 retry, reset, accommodation, or exceptional resume authority;
- D09 offline, late-save, grace-period, or outage treatment;
- scoring, feedback, release contents, regrade, or result creation.

No unresolved behavior may be inferred from the term `submitted`.

## Persistence and transaction contract

- The published revision retains the configured close instant.
- PostgreSQL connections use UTC so timezone-aware instants survive persistence
  and reload without an Asia/Manila wall-clock shift.
- A started attempt retains the close, optional session deadline, and effective
  deadline calculated at Start; reconnect never recalculates them.
- Terminal transition uses a PostgreSQL transaction and row lock or equivalent
  conditional transition.
- Automatic close records the close as the logical `submitted_at` and may record
  the later processing instant separately for operations/audit.
- The last acknowledged answers are not rewritten during finalization.

## Acceptance criteria

- Start succeeds immediately before close and fails exactly at or after close.
- A late start receives a deadline capped by the global close.
- Active question deadlines cannot exceed the global close.
- Unanswered questions remain unanswered while acknowledged answers persist.
- Both serialized outcomes of a submit/close race create one terminal attempt.
- Duplicate finalization returns the same persisted outcome.
- Browser time cannot alter a server decision.
- A delayed worker preserves the close as the logical submission instant.
- Reconnection after close returns the same terminal state and deadlines.

## Delivery boundary

Phase 5E may add dormant deadline fields, pure guards, transaction seams, and
tests. It must not register Start, answer-save, timer-expiry, scheduler, or
student submission routes until their remaining prerequisite decisions are
approved and tested.

**Revisit** only through another dated product-owner decision that explicitly
changes D07.

## Subsequent decision

ADR-006, approved 23 October 2026, resolves the ordinary V1 D04-D06 and D08-D10
policies excluded above. ADR-005 remains authoritative for D07 and its global
close continues to cap every question and session deadline.
