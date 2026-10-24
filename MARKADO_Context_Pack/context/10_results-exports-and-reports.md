# Results, Score Ranking, Alphabetical Rosters and PDF/DOCX Exports

## Requested result views [REQ]
**Alphabetical view:** list students by name. **Score ranking:** sort from highest to lowest score. **Exports:** PDF and Word are both supported output requirements. Permission, release, grading and filtering details must remain consistent.

## Recommended results table [REC]
Columns: student name, student number only if authorized, attempt status, submission timestamp, earned points, total points, percentage if approved, result release state, and instructor action indicators. Avoid disclosing student email by default in exported files unless necessary and approved.

## Deterministic sorts [REC]
- Alphabetical: normalized last name, first name, middle name if present, then stable student ID as tie breaker. Confirm naming conventions before implementation.
- Score order: descending earned score on a common basis, then a documented tie policy (e.g. alphabetical as a stable presentation tie-breaker). **Tied scores remain tied academically**; list-position numbering is not necessarily competition rank.
- If quizzes allow multiple attempts, decide which attempt contributes to the roster [OPEN].

## Results release [DECIDED WITH EXCLUSIONS]
ADR-007 requires the authorized owning Professor to manually release a
finalized, successfully scored result. Before release, the student sees no
score. After release, the student sees only earned integer points and total
possible points. Correct answers, item-level correctness, explanations, other
students' results, and a student leaderboard remain hidden. Bulk/scheduled or
revoked release, ranking/ties, exports, overrides, and regrading still require
separate decisions.

## Filters [REC]
Quiz; class/section if such grouping exists; completion state; attempt number if enabled; sort mode; score status; date range; optional search. The exported artifact should show the applied filters and a clear generation timestamp/timezone.

## PDF report contract [REC]
Cover/header: system name, quiz title, quiz ID or readable reference, instructor/scope if authorized, applied sort and filters, generated-at timestamp and page numbers. Table rows should wrap for long student names. Repeat column headers on subsequent pages, protect page breaks, show total records and legend for incomplete/pending states. Do not expose answer keys in ordinary class results.

## Word report contract [REC]
DOCX report should carry the same authorized dataset and ordering as PDF; editable tables, repeated headers where feasible, clear title, footer and pagination conventions. DOCX is not a security boundary: exported personal data remains portable and should be handled with care.

## Export generation pipeline [REC]
1. Server checks actor and result scope.
2. Query authorized attempt snapshots and scores with selected filters.
3. Normalize ordering deterministically.
4. Produce a consistent intermediate report model.
5. Render PDF and DOCX from the **same** report model.
6. Log generation metadata and make the file available only to the requester within policy.
7. Apply retention/expiry to stored exports if saved.

## Mathematical correctness [REC]
Use stored max points and earned points; avoid floating-point surprises using integer/decimal arithmetic. If question weights differ, score ranking must compare on a product-approved basis. Do not rank learners with incomparable grading schemes without labeling the difference.

## Export test cases [REC]
- Two students share the same name; tie is stable by unique ID.
- Three scores tie; display labels are consistent in screen/PDF/DOCX.
- Student with long accented name remains legible; alphabetical collation is documented.
- Learner with no submitted attempt is shown or omitted according to approved roster policy.
- Score regrade updates final export but preserves source audit trail.
- Unauthorized instructor cannot export another class's records via guessed route.
- Filtered screen set and PDF/DOCX contain identical roster IDs, order, statuses and totals.
- Large export is paginated, does not truncate and remains accessible.
