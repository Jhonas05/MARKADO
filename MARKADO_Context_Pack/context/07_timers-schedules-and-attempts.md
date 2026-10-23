# Timers, Availability, Deadline Semantics and Attempt Consistency

## Requested timer types [REQ]
- **Per-question timer:** configured duration associated with an individual question.
- **Quiz session timer:** configured overall attempt duration.
- **Date/time availability window:** separate schedule during which a quiz can be accessed/started.

These are **three distinct concepts**. They must not be conflated in UI text or database fields.

## Deadline model [REC]
For the approved **online Laravel deployment [DECIDED]**, persist `started_at` from an authoritative server clock and calculate `quiz_deadline = started_at + quiz_duration`. If both timers operate, persist the question's actual activation time and deadline as well; effective answer acceptance uses the configured policy. On reconnect, the client fetches the stored deadlines; it never reissues new ones.

**D07 [DECIDED]:** The global closing time truncates every attempt deadline. A student may start strictly before close even if the configured session duration will not fit. At close, active attempts are automatically submitted using the latest server-acknowledged answers. Per-question expiry, general session-duration expiry, accommodation/outage behavior, and whether both timers may be set concurrently remain [OPEN].

## Example - competing clocks [REC]
Availability: 09:00 to 10:00, timezone Asia/Manila; attempt starts 09:50; 20-minute quiz duration. ADR-005 selects a hard close: the attempt may start and receives an effective deadline of 10:00. At 10:00 the server automatically submits it. A start at exactly 10:00 is denied.

## Trusted clock in the approved online Laravel app [DECIDED + REC]
Use the **central Laravel application/database time** as the source for accepted start, save, submit and expiry decisions; store deadline timestamps durably, preferably in UTC with explicit display conversion. The JavaScript/browser timer is **display-only** and cannot extend an attempt. All Laravel web workers and queue workers must interpret the same persisted instants and policy. A delayed close job must preserve the scheduled close as the logical submission deadline; request-time enforcement remains authoritative. D07 fixes the exact close boundary, while other D10 schedule semantics remain [OPEN].

## Online connectivity and reconciliation [REC; outage policy OPEN]
- On refresh, tab change or reconnection, fetch the current attempt, saved answers, and unchanged persisted deadlines from Laravel.
- At or after the D07 close, reconnection returns the same automatically submitted terminal attempt; it never reopens or extends it.
- If the network drops, distinguish **unsaved locally displayed input** from **server-confirmed saved answers**; do not promise offline assessment or automatic acceptance of late work.
- Retrying an answer save or final submit must never create a second attempt or grade.
- Whether to allow any late retry/grace period or administrator accommodation remains D09 [OPEN].

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
