# End-to-End Journeys, Edge Cases and State Machines

## Instructor journey [REQ + REC]
1. Authenticate and open **My Quizzes**.
2. Create quiz title/details and select assignment scope (assignment scope [OPEN]).
3. Add questions manually or upload permitted materials to generate editable drafts.
4. Verify question text, correct answers, scoring and accessibility.
5. Configure per-question/quiz timers, availability, randomization and result release policy.
6. Preview as a student, review publish checklist, then publish.
7. Distribute unique code/link; view participation and completed attempts.
8. Open alphabetical or score-ranked results and export to PDF/DOCX.

**Exception paths:** Empty quiz, missing correct answer, draft AI items, invalid schedule, changed owner permissions, expired publishing token, and publishing while already-registered attempts exist.

## Student journey [REQ + REC]
1. Register with student number and email, or sign in.
2. Enter code or open link; authentication remains required.
3. Check authorization, quiz state and allowed time window.
4. View quiz instructions, question count, timer rules, navigation restrictions and release policy.
5. Explicitly start an attempt; preserve an immutable start record.
6. Answer/advance. Save and render acknowledgements without misrepresenting network state.
7. Submit voluntarily or handle expiration according to the approved policy.
8. Receive a submission receipt; see released result if permitted.

**Exception paths:** Already used student number/email, duplicate join, missing connectivity, expired code, server clock disagreement, new tab, stale question version, submit timeout, and resume after partial save.

## Administrator journey [REC]
1. Authenticate with authorized account.
2. Manage requested student/instructor account support actions within scope.
3. Review suspicious duplicate registrations or account recovery cases without exposing answer keys.
4. Inspect relevant audit trail and export activity only under a documented policy.
5. Resolve operational incident with an explicit case/decision record.

## Proposed quiz state machine [REC]
`DRAFT -> READY_FOR_REVIEW -> PUBLISHED -> CLOSED -> ARCHIVED`

- **DRAFT:** editable, not joinable.
- **READY_FOR_REVIEW:** passes validation, awaiting publish approval.
- **PUBLISHED:** joinable when schedule and eligibility permit; schedule need not be currently open.
- **CLOSED:** new starts disabled. At a scheduled D07 close, active attempts are
  automatically submitted using acknowledged answers. Instructor-initiated
  unpublish and other exceptional closure paths remain separately governed.
- **ARCHIVED:** historical only; retrieval/retention governed by policy.
- **Reopen / unpublish / revise:** [OPEN]; do not add edges until product owner approves.

## Proposed attempt state machine [REC]
`NOT_STARTED -> IN_PROGRESS -> SUBMITTED` plus `EXPIRED` and optional `INTERRUPTED`/`VOIDED`.

- `IN_PROGRESS` exists once the start action is accepted transactionally.
- `SUBMITTED` is terminal unless a separately audited regrade changes **result**, not original answers.
- `EXPIRED` is terminal under an approved expiry policy; its score outcome may still require grading.
- `INTERRUPTED` should **not** be equated to expired simply because a browser closes.
- `VOIDED` is an exceptional authorized administrative action [OPEN].

## Idempotent submission scenario [REC]
If student presses Submit twice or retries following a network timeout, both requests reference the **same attempt and submission idempotency key**. Only the first accepted transition changes state. Subsequent retries return the same receipt/state without creating extra attempts or scores.

## Precondition / postcondition pattern [REC]
**Start attempt:** preconditions = authenticated, eligible, quiz published, in availability window, attempt limit satisfied. Postconditions = attempt ID + attempt snapshot + authoritative deadline stored. **Submit:** preconditions = owned, active, within allowed submit policy. Postconditions = fixed submission timestamp + immutable response snapshot + acknowledgement.
