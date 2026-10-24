# Timers, Availability, Deadline Semantics and Attempt Consistency

## Requested timer types [REQ]
- **Per-question timer:** configured duration associated with an individual question.
- **Quiz session timer:** configured overall attempt duration.
- **Date/time availability window:** separate schedule during which a quiz can be accessed/started.

These are **three distinct concepts**. They must not be conflated in UI text or database fields.

## Deadline model [DECIDED]
For the approved **online Laravel deployment**, persist `started_at` from an authoritative server clock and calculate the whole-session deadline from it. Both timer types may run simultaneously. Persist each question's actual activation time and deadline; the earliest applicable persisted question, session, or global-close deadline wins. A question deadline never exceeds the effective attempt deadline. On resume or reconnect, the client fetches the stored deadlines and never reissues new ones.

**D04-D06 [DECIDED by ADR-006 and ADR-007]:** Question expiry locks the question, preserves its last acknowledged answer, rejects later mutations, and advances once to the next available question with no backward navigation. Whole-session expiry atomically finalizes the attempt using acknowledged answers and returns a stable receipt. ADR-007 decides that expiry of the final question atomically finalizes the attempt at that persisted question deadline.

**D07 [DECIDED by ADR-005]:** The global closing time truncates every attempt deadline. A student may start strictly before close even if the configured session duration will not fit. At close, active attempts are automatically submitted using the latest server-acknowledged answers.

## Example - competing clocks [REC]
Availability: 09:00 to 10:00, timezone Asia/Manila; attempt starts 09:50; 20-minute quiz duration. ADR-005 selects a hard close: the attempt may start and receives an effective deadline of 10:00. At 10:00 the server automatically submits it. A start at exactly 10:00 is denied.

## Trusted clock in the approved online Laravel app [DECIDED]
Use the **central Laravel application/database time** as the source for accepted start, save, submit, and expiry decisions. Store authoritative instants in UTC and display them in `Asia/Manila`. Opening is inclusive and closing is exclusive for new starts: `opens_at <= authoritative_now < closes_at`. The JavaScript/browser timer is **display-only** and cannot extend an attempt. All Laravel web and queue workers interpret the same persisted instants and policy. A delayed close job preserves the scheduled close as the logical submission deadline; request-time enforcement remains authoritative.

## Online connectivity and reconciliation [DECIDED]
- On refresh, sign-in, browser restart, tab/device change, or reconnection, resume the same attempt and fetch its unchanged identifiers, ordering, acknowledged answers, and persisted deadlines from Laravel.
- At or after the D07 close, reconnection returns the same automatically submitted terminal attempt; it never reopens or extends it.
- If the network drops, timers continue and the UI distinguishes **Saving**, **Saved**, **Connection Lost**, and **Save Failed**. It never labels local input as saved without a Laravel acknowledgement.
- Stable idempotency keys and version-aware updates make save/finalize retries safe and reject stale or late mutations.
- No automatic grace period or offline-exam promise is approved. Institution-wide outage remedies and staff accommodations remain [OPEN].

## Race-condition scenarios [REC]
- **Boundary start:** A start strictly before close may proceed; a start at or after close is denied by the authoritative server clock.
- **Submit/close race:** Student submission and hard-close finalization must perform one atomic state transition; exactly one terminal outcome.
- **Answer save/expiry race:** Stale requests cannot rewrite a locked answer or extend a question deadline.
- **Multiple tab race:** Active attempt/answer state cannot fork into two independent timelines.
- **Edit/live attempt race:** Content revisions cannot alter already-issued question snapshots invisibly.
- **Retry/idempotency:** Requests carry stable attempt/submission IDs and return consistent results.

## Student-facing UI behavior [REC]
Persistent visible countdown; high-salience but non-panic warnings (e.g. near expiration) without disruptive flashing; text notice before attempt; timezone label; clear submit confirmation; separate "saved" vs "submitted" status. Screen reader announcements must be useful but not constantly interruptive.

## Required tests [REC]
Use a controllable test clock; test at start/close timestamps, just-before and just-after boundaries, expired response payloads, browser reload, second tab, concurrent submit/timeout handlers, network retry, system time changes, long-running browser sessions, leap day and timezone conversion when relevant.
