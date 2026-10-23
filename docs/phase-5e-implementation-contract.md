# Phase 5E assessment-engine implementation contract

**Updated:** 23 October 2026

**Status:** D07 hard close is approved by ADR-005. D04-D06 and D08-D10 are
approved within the exact ADR-006 boundaries. Existing Phase 5E seams remain
dormant until their routes, authorization, PostgreSQL concurrency behavior, and
acceptance tests are implemented and verified. This document does not approve
scoring, released feedback, public self-verification, privileged overrides, or
the final-question per-question-expiry transition.

## Approved deadline hierarchy

- Persist all authoritative instants in UTC; display school-facing time in
  `Asia/Manila`.
- Permit Start only while `opens_at <= authoritative_now < closes_at` and all
  authentication, publication, and eligibility requirements pass.
- Do not require the complete session duration to fit before close.
- When a whole-session timer is configured, persist:

  `effective_attempt_deadline = MIN(started_at + configured_duration, quiz_closes_at)`

- When a per-question timer is configured, persist:

  `effective_question_deadline = MIN(question_started_at + question_duration, effective_attempt_deadline)`

- The earliest reached question, session, or global deadline controls. React
  countdowns are display-only and browser time is absent from the decision API.
- Resume returns original timestamps and never recalculates time from reconnect.

## Approved question-expiry contract

- At or after the active question deadline, reject answer mutations for that
  question and preserve the last server-acknowledged answer.
- Lock the question and advance exactly once to the next available question.
- Disable backward navigation whenever per-question timing is enabled.
- Use a persisted activation/deadline record so refresh, another tab/device, or
  reconnect cannot issue fresh question time.
- Keep the final-question expiry transition disabled until its explicit policy
  is approved; do not invent extra time or an early terminal state.

## Approved whole-session and global finalization contract

- At the effective attempt deadline, atomically finalize every active attempt
  using the last acknowledged answers; unanswered items remain unanswered.
- Preserve the applicable persisted deadline as the logical terminal timestamp,
  even when a request or worker observes it later.
- Reject later answer mutations.
- Treat student submission, session expiry, global close, delayed workers, and
  retries as competing idempotent requests for one terminal transition under a
  row lock or equivalent conditional write.
- Return the same stable receipt after duplicate terminal requests.

## Approved resume and connectivity contract

- Enforce one attempt per student per quiz and make duplicate Start idempotently
  return that attempt.
- Resume the same attempt after refresh, browser restart, sign-in, temporary
  connection loss, or another tab/device.
- Preserve immutable revision/snapshots, question and choice order, acknowledged
  answers, versions, and deadlines.
- Do not permit student reset for fresh content, time, order, or another attempt.
- Accept an answer as saved only after Laravel persists it and returns an
  acknowledgement with the request idempotency key, authoritative version, and
  persisted time.
- Reject stale, out-of-order, cross-attempt, locked, expired, or terminal writes.
- During disconnection, timers continue and the UI truthfully distinguishes
  `Saving`, `Saved`, `Connection Lost`, `Save Failed`, `Submitted`, and `Expired`.
- No automatic grace period or offline examination mode is approved.

## Prepared Phase 5E code

`HardCloseDeadlineGuard` accepts only an injected `AuthoritativeClock`. It
decides the strict D07 Start boundary, calculates the approved minimum attempt
deadline, caps question deadlines, and detects a reached deadline.

`AttemptFinalizationCoordinator` serializes student-submit and quiz-close
transitions, preserves an existing terminal outcome, and returns a stable
receipt. It currently has no production route, scheduler, interface binding, or
enabled capability.

The additive Phase 5E migration prepares nullable published-revision and attempt
deadline/finalization fields. PostgreSQL rejects an effective attempt deadline
that is not exactly the minimum of its persisted close and optional session
deadline. The Laravel PostgreSQL connection is pinned to UTC.

## Acceptance traceability

| ID | Scenario | Expected evidence |
|---|---|---|
| D04-01 | Both timer types active | Earliest persisted applicable deadline wins |
| D04-02 | Question limit exceeds session/global remainder | Question deadline is capped |
| D05-01 | Save races question expiry | Row/version serialization yields one authoritative result |
| D05-02 | Question expires with another available | Locked once; last acknowledgement retained; advances once |
| D05-03 | Back navigation requested | Denied while per-question timing is enabled |
| D05-04 | Refresh/reconnect during active question | Same question deadline returned |
| D05-05 | Final question expires | Remains gated until explicit edge policy |
| D06-01 | Whole-session deadline reached | One terminal attempt and stable receipt |
| D06-02 | Duplicate expiry/finalize requests | Same receipt; no duplicate terminal state/result |
| D07-01 | Start immediately before close | Allowed using server time |
| D07-02 | Start exactly at/after close | Denied |
| D07-03 | 09:50 start, 20-minute session, 10:00 close | Effective deadline is 10:00 |
| D07-04 | Unanswered items at close | Acknowledged answers retained; others remain unanswered |
| D07-05 | Submit/finalization race | Both serialization orders converge on one terminal attempt |
| D07-06 | Delayed worker | Logical submission remains the persisted deadline |
| D08-01 | Duplicate or concurrent Start | One attempt returned |
| D08-02 | Refresh/sign-in/tab/device resume | Same attempt, order, answers, versions, and deadlines |
| D08-03 | Concurrent answer sessions | Stale version cannot overwrite the authoritative answer |
| D09-01 | Connection lost before acknowledgement | UI never reports Saved; local input is non-authoritative |
| D09-02 | Lost acknowledgement then retry | Idempotency returns one persisted mutation |
| D09-03 | Reconnect after expiry | Locked/terminal authoritative state; no grace or extension |
| D10-01 | Exactly at opening | Start may proceed if all other gates pass |
| D10-02 | Exactly at closing | Start denied |
| D10-03 | Browser clock differs | No effect on Start/save/deadline/finalization decisions |
| D10-04 | UTC instant displayed | Correct Asia/Manila value and label |

## Still blocked

- **D05 final-question edge:** no automatic transition is selected yet.
- **D08/D09 exceptions:** staff reset, accommodation, override, and institution-
  wide outage remedies remain unresolved and disabled.
- **D11/D12:** released feedback, scoring, weighting, rounding, ties, and ranking.
- **D13-D15/D23:** identity-verification, account recovery/provisioning,
  privileged administration, reset, regrade, and appeal authority.
- Production activation still requires implemented authorization, migrations,
  request contracts, PostgreSQL concurrency tests, operations/queue controls,
  privacy review, and desktop/mobile/accessibility verification.

These blockers do not prevent incremental implementation of the approved,
policy-independent contracts behind disabled capabilities and trusted synthetic
fixtures.
