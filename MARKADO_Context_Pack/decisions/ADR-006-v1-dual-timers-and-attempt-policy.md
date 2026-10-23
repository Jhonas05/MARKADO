# ADR-006: V1 dual timers, expiry, resume, connectivity, and schedule boundaries

**Status:** APPROVED by product owner

**Approval date:** 23 October 2026

**Decision scope:** D04, D05, D06, D08, D09, and D10 only, with the explicit
exclusions below. ADR-005 remains authoritative for D07 global hard close.

## Context

ADR-003 approved one resumable attempt and Asia/Manila display time. ADR-005
then approved the global hard close. The product owner has now approved the
remaining ordinary timer, resume, connectivity, and schedule-boundary rules
needed to implement a server-authoritative V1 attempt engine without deciding
scoring, released feedback, identity-authority, or privileged override policy.

## D04: dual timers

1. A quiz may use a whole-quiz timer, a per-question timer, or both at once.
2. The earliest applicable persisted deadline controls the next permitted
   action. An active question deadline is capped by the question limit, the
   effective whole-session deadline, and the global quiz close.
3. Reaching a question deadline locks that question. Reaching the whole-session
   or global deadline terminates the attempt according to D06 or D07.
4. Laravel/PostgreSQL time is authoritative. React countdowns are display-only
   and may never grant, renew, or extend time.
5. The interface displays the applicable question and whole-quiz remaining time
   clearly without representing browser time as authoritative.

## D05: per-question expiration

1. When the persisted deadline for the active question is reached, the server
   locks that question and rejects later answer mutations for it.
2. The last answer acknowledged and persisted by the server is preserved. An
   unacknowledged browser selection is not counted as saved.
3. The attempt advances to the next available question automatically.
4. Backward navigation is unavailable while per-question timing is enabled.
5. Refresh, reconnect, another tab, another device, or a changed browser clock
   never regenerates or extends the question deadline.
6. If the expired question is the final question and no next question exists,
   the end-of-assessment transition remains **UNRESOLVED**. The implementation
   must not invent extra time, early finalization, or another terminal outcome.

## D06: whole-quiz expiration

1. When the persisted whole-session deadline is reached, the server atomically
   finalizes the attempt.
2. Only answers already acknowledged and persisted by the server are retained;
   unanswered questions remain unanswered.
3. Further answer mutations are rejected.
4. Finalization returns a stable receipt. Duplicate requests and competing
   expiry handlers converge on the same terminal outcome.
5. If the global quiz close occurs first, ADR-005 controls and the earlier
   global deadline wins.

## D08: one attempt and resume

1. A student has one attempt per quiz.
2. An unfinished attempt resumes after refresh, browser restart, signing in
   again, temporary connection loss, or opening the same attempt in another tab
   or device.
3. Resume preserves the original attempt identifier, immutable revision and
   snapshots, question order, choice order, acknowledged answers, and all
   persisted deadlines.
4. A student cannot reset the attempt to obtain new content, a new order, new
   time, or a second attempt.
5. Concurrent sessions must use version-aware, server-serialized mutations so
   they cannot fork the authoritative answer timeline.
6. Staff reset, accommodation, retry, and override authority remains
   **UNRESOLVED and disabled** under D15/D23.

## D09: connectivity loss and answer acknowledgement

1. Timers continue while connectivity is lost; no automatic grace period or
   extra time is granted.
2. The client must never label an unacknowledged answer as saved. Answers count
   only after Laravel successfully persists them and returns an acknowledgement.
3. On reconnection, the client reloads the latest authoritative attempt state,
   original ordering, original deadlines, and confirmed answers.
4. Answer changes received after the applicable deadline are rejected.
5. Stable idempotency keys and version-aware updates make retries safe and
   prevent duplicate or out-of-order requests from corrupting answers.
6. The UI distinguishes `Saving`, `Saved`, `Connection Lost`, `Save Failed`,
   `Submitted`, and `Expired` accurately.
7. MARKADO does not promise offline examination capability. Institution-wide
   outage remedies remain **UNRESOLVED** and require a separate decision.

## D10: opening, closing, storage, and display time

1. Opening time is inclusive and closing time is exclusive for new starts:

   `opens_at <= authoritative_now < closes_at`

2. Persist authoritative instants unambiguously in UTC.
3. Display school-facing dates and times in `Asia/Manila`.
4. Browser time cannot decide availability, acceptance, expiry, or extension.
5. ADR-005 still governs active attempts at the closing instant.

## Cross-decision deadline contract

For an attempt with an approved whole-session duration:

`effective_attempt_deadline = MIN(started_at + configured_duration, quiz_closes_at)`

For an active question when per-question timing is enabled:

`effective_question_deadline = MIN(question_started_at + question_duration, effective_attempt_deadline)`

All operands are persisted server timestamps. Resume returns them unchanged.

## Student-facing contract

- Before Start, state the availability window, timer modes, sequential
  navigation restriction, auto-finalization behavior, connectivity limitation,
  and `Asia/Manila` timezone.
- During an attempt, show authoritative deadline-derived countdowns and truthful
  save/connection/terminal states.
- Expired controls become unavailable with an accessible explanation; the
  client never implies that an unsaved selection was accepted.

## Acceptance traceability

| ID | Scenario | Required result |
|---|---|---|
| D04-01 | Per-question and whole-quiz timers enabled together | Earliest applicable persisted deadline wins |
| D04-02 | Question deadline would exceed session/global deadline | Question deadline is capped |
| D04-03 | Browser clock changes | Server decision and persisted deadlines are unchanged |
| D05-01 | Answer arrives before question expiry | Server may persist and acknowledge it once |
| D05-02 | Answer arrives at/after question expiry | Mutation rejected; last acknowledged answer retained |
| D05-03 | Question expires with another question available | Current question locks and attempt advances once |
| D05-04 | Refresh/reconnect during a question | Original question deadline is returned unchanged |
| D05-05 | Student requests a previous question under per-question timing | Backward navigation denied |
| D05-06 | Final question expires | Remains gated until the explicit edge policy is approved |
| D06-01 | Whole-session deadline is reached | Attempt finalizes once with acknowledged answers only |
| D06-02 | Duplicate expiry/finalize requests | Same stable receipt; no duplicate terminal records |
| D06-03 | Whole-session and global close race | Earlier deadline controls; one terminal outcome |
| D08-01 | Duplicate or concurrent Start | One attempt is returned |
| D08-02 | Refresh/sign-in/browser/device resume | Same ID, order, answers, and deadlines |
| D08-03 | Multiple-tab answer race | Version conflict cannot silently overwrite newer state |
| D08-04 | Student requests reset/new time | Denied |
| D09-01 | Connection lost after local selection | UI does not report Saved without acknowledgement |
| D09-02 | Lost acknowledgement then idempotent retry | One persisted answer/version is returned |
| D09-03 | Out-of-order save | Stale version rejected without corruption |
| D09-04 | Reconnect after a deadline | Authoritative locked/terminal state is returned; no grace |
| D10-01 | Start exactly at opening | Allowed if every other precondition passes |
| D10-02 | Start immediately before closing | Allowed if every other precondition passes |
| D10-03 | Start exactly at/after closing | Denied |
| D10-04 | Stored UTC instant displayed to school users | Correct `Asia/Manila` value and label |

## Security, transaction, and audit consequences

- Start, answer save, question advancement, and finalization are authorized
  Laravel operations over the authenticated student's own eligible attempt.
- Attempt creation and terminal transitions use PostgreSQL uniqueness plus row
  locking or equivalent conditional writes.
- Answer acknowledgements include a stable request/idempotency identifier,
  server version, and persisted timestamp without exposing answer keys.
- Audit events may record identifiers, transition reasons, deadlines, request
  correlation, and result codes, but not plaintext credentials or protected
  answer content.
- Request-time deadline enforcement remains authoritative when a queue worker is
  delayed. Workers cannot create additional allowed time.

## Explicit exclusions and blockers

This ADR does not decide D11 released feedback, D12 scoring/weighting/rounding/
ranking, D13 student-number authority, D14 email verification/recovery, D15
privileged administration, D23 reset/accommodation/regrade/appeal authority,
or the D05 final-question edge. Those behaviors remain gated.

Public registration and unrestricted privileged account creation remain
disabled. Production activation also requires the related authorization,
privacy, migration, operations, and acceptance-test gates to pass.

## Revisit criteria

Revisit only through another dated product-owner decision that changes timer
precedence, expiry transitions, resume identity, acknowledgement semantics,
schedule boundaries, or exceptional outage/override handling.
