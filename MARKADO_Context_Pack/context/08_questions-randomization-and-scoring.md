# Question Formats, Randomization and Scoring Integrity

## Format choices [OPEN]
The provided source does not approve a final V1 set. Candidate formats [REC] are multiple choice single answer, true/false, multi-select, short identification, matching, and essay/manual evaluation. Before coding, decide for each: input shape, automatic scoring support, points, partial credit, case sensitivity, spelling tolerance, and keyboard accessibility.

## Canonical question object [REC]
Question ID, revision ID, kind, prompt, feedback/explanation, option records with **stable IDs**, answer key/rubric kept server-side, points, optional source/provenance, review status, owner/scope, and accessibility description for media. Do not place secret answer keys inside join-page API payloads.

## Randomization contract [REQ + REC]
For each attempt, create a permutation of eligible questions and (where semantically valid) a separate permutation of answer choices. Store the mapping by persistent IDs, never by display index. Render numbering using the attempt order; grade by stable question/option identity. Reconstruct the exact order for audit and instructor review.

**Illustration:** Stored correct option ID `opt-7`; choices display as positions A,B,C,D in randomized order. If `opt-7` appears as C, grading compares submitted option ID with `opt-7`, not the letter C in the canonical template.

## Anti-errors [REC]
- Never reorder labeled "All of the above" or sequence-dependent options without adjusting semantics.
- A single answer can have only one canonical correct option unless a multi-select question type is explicitly defined.
- Reject malformed choices, missing answers, duplicated option IDs and negative scores.
- Validate scoring at publish, at start snapshot, and at grade computation boundaries.

## Score computation [REC]
`earned_points = sum(question_result_awarded_points)` and `max_points = sum(attempt_snapshot_question_max_points)`. If a percentage is required, define rounding and decimal places before export. Do not assume grades are percentages when source only says score.

## Manual review / regrade [OPEN]
If essay or score adjustments are enabled, define grader authority, original score preservation, change reason, grading timestamp, result-release implications and whether students are notified. Audit the **adjustment** rather than mutating submitted answers.

## Fairness and integrity disclaimers
Question randomization can make attempts less predictable but does **not** guarantee prevention of collusion or cheating. Avoid oppressive surveillance or unsupported "AI cheating detection" claims. Use clear rules, sound assessment construction and staff review.
