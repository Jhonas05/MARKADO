# Timers, Availability, Deadline Semantics and Attempt Consistency

## Requested timer types [REQ]
- **Per-question timer:** configured duration associated with an individual question.
- **Quiz session timer:** configured overall attempt duration.
- **Date/time availability window:** separate schedule during which a quiz can be accessed/started.

These are **three distinct concepts**. They must not be conflated in UI text or database fields.

## Deadline model [REC]
For the approved **online Laravel deployment [DECIDED]**, persist `started_at` from an authoritative server clock and calculate `quiz_deadline = started_at + quiz_duration`. If both timers operate, persist the question's actual activation time and deadline as well; effective answer acceptance uses the configured policy. On reconnect, the client fetches the stored deadlines; it never reissues new ones.

**Important [OPEN]:** Whether the global closing time truncates the attempt deadline; whether per-question expiry auto-advances; whether quiz expiry auto-submits; whether timers pause for accommodation/outage; whether both timers may be set concurrently. Implement none of these as invisible defaults.

## Example - competing clocks [REC]
Availability: 09:00 to 10:00, timezone Asia/Manila; attempt starts 09:50; 20-minute quiz duration. Possible policies: **A** deny start because remaining window insufficient; **B** allow attempt until 10:10; **C** allow start but enforce 10:00 finish. This pack does **not** choose among them. The chosen policy must appear in pre-start instructions.

## Trusted clock in the approved online Laravel app [DECIDED + REC]
Use the **central Laravel application/database time** as the source for accepted start, save, submit and expiry decisions; store deadline timestamps durably, preferably in UTC with explicit display conversion. The JavaScript/browser timer is **display-only** and cannot extend an attempt. All Laravel web workers and queue workers must interpret the same persisted instants and policy. A delayed scheduled expiry job must not extend the allowed deadline: attempt requests still check authoritative deadlines themselves. The precise timezone and boundary semantics remain D10 [OPEN].

## Online connectivity and reconciliation [REC; outage policy OPEN]
- On refresh, tab change or reconnection, fetch the current attempt, saved answers, and unchanged persisted deadlines from Laravel.
- If the network drops, distinguish **unsaved locally displayed input** from **server-confirmed saved answers**; do not promise offline assessment or automatic acceptance of late work.
- Retrying an answer save or final submit must never create a second attempt or grade.
- Whether to allow any late retry/grace period or administrator accommodation remains D09 [OPEN].

## Race-condition scenarios [REC]
- **Boundary start:** Two starts at the close timestamp must be checked against the same authoritative rule.
- **Submit/expire race:** Submission and timeout handler must perform an atomic state transition; exactly one final outcome.
- **Answer save/expiry race:** Stale requests cannot rewrite a locked answer or extend a question deadline.
- **Multiple tab race:** Active attempt/answer state cannot fork into two independent timelines.
- **Edit/live attempt race:** Content revisions cannot alter already-issued question snapshots invisibly.
- **Retry/idempotency:** Requests carry stable attempt/submission IDs and return consistent results.

## Student-facing UI behavior [REC]
Persistent visible countdown; high-salience but non-panic warnings (e.g. near expiration) without disruptive flashing; text notice before attempt; timezone label; clear submit confirmation; separate "saved" vs "submitted" status. Screen reader announcements must be useful but not constantly interruptive.

## Required tests [REC]
Use a controllable test clock; test at start/close timestamps, just-before and just-after boundaries, expired response payloads, browser reload, second tab, concurrent submit/timeout handlers, network retry, system time changes, long-running browser sessions, leap day and timezone conversion when relevant.
