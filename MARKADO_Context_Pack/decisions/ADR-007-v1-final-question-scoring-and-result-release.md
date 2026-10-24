# ADR-007: V1 final-question expiry, objective scoring, and minimal result release

**Status:** APPROVED by product owner

**Documentation date:** 24 October 2026

**Approval evidence:** The product owner explicitly approved the minimal
D05/D11/D12 recommendation table by replying `g pre` in the MARKADO project
conversation. The repository record does not contain a separate approval date
or timestamp, so none is inferred here. No academic-committee approval is
claimed.

**Decision scope:** The exact D05, D11, and D12 rules below. Ranking, exports,
identity authority, privileged intervention, regrading, and other exclusions
remain governed by their existing decision records.

## Context

ADR-006 approved per-question expiry but intentionally left the final-question
terminal transition unresolved. ADR-003 approved Professor-controlled manual
result release while leaving score calculation and released feedback open.
This decision supplies the smallest objective V1 rules needed to complete the
database-backed MCQ and True/False flow without inventing grading or ranking
policy.

## D05: final-question expiry

1. When the final question's persisted per-question deadline is reached, the
   server automatically finalizes the entire attempt even when whole-quiz time
   remains.
2. The latest server-acknowledged answers remain persisted; unanswered items
   remain unanswered.
3. An earlier whole-session deadline or global quiz close still takes
   precedence.
4. The rule applies only when per-question timing is enabled.
5. Selecting or saving an answer on the final question is not final submission.
6. Finalization is atomic and idempotent and preserves the logical deadline
   separately from the later processing time.

## D12: objective V1 scoring

1. Each immutable single-answer MCQ or True/False snapshot is worth one point.
2. A correct stable choice identifier earns one point. An incorrect or
   unanswered item earns zero points.
3. There is no negative marking, partial credit, or question weighting.
4. Earned and maximum points are integers. Maximum points come from the
   immutable attempt snapshot, never a later quiz revision.
5. V1 does not calculate or expose percentage grades, pass/fail thresholds,
   curves, rank numbers, or tie policy.
6. Only terminal attempts may be scored. A retry returns the one persisted
   result and cannot silently overwrite it.

## D11: manual release and minimal student feedback

1. Only the authorized Professor who owns the quiz may manually release a
   finalized, successfully scored result.
2. Before release, the student may see quiz identification, terminal status,
   and a submission receipt, but no earned score or correctness information.
3. After release, the student sees only earned points and total possible points
   as academic feedback.
4. Correct answers, selected-item correctness, explanations, and every other
   student's result remain hidden.
5. No student leaderboard is approved for V1.
6. Result release is transactional, audited, and repeat-safe. Administrator
   status alone does not grant release authority.
7. Existing instructor reporting/export requirements remain in scope, but rank
   numbering, ties, bulk/scheduled release, revocation, override, and regrading
   require separate decisions.

## Deadline reconciliation consequence

The authoritative terminal event is the earliest applicable logical event:
final-question expiry, whole-session expiry, global close, or a valid timely
manual submission. Handlers compare persisted event timestamps rather than the
order in which a request or worker observes them. When deadlines are equal, the
deterministic precedence is global close, whole-session expiry, then final-
question expiry. One terminal receipt is persisted in every case.

For example, if the final question expired at 09:56, the quiz closed at 10:00,
and reconciliation ran at 10:02, the logical submission time is 09:56 while
the processing time records 10:02.

## Acceptance criteria

- Final-question expiry before the session/global deadline finalizes once at
  that question deadline and preserves acknowledged answers.
- Competing finalization paths converge on one terminal receipt and do not
  modify terminal answers.
- Objective scoring uses snapshot choice identifiers and supports 0/N, N/N,
  incorrect, and unanswered results.
- A later quiz revision cannot change a persisted historical score.
- Cross-student and cross-instructor result access is denied before any
  idempotent replay is disclosed.
- Student projections contain no score before release and only score/total
  afterward; answer keys and item correctness never appear.
- Repeated authorized release returns the existing released state without a
  second release event.

## Explicit exclusions

This ADR does not decide D13/D14 identity issuance, verification, registration,
or recovery; D15/D23 privileged resets, accommodations, overrides, regrading,
or appeals; institution-wide outage remedies; voluntary submission containing
unanswered questions; result-release revocation; ranking or tie presentation;
or PDF/DOCX export details.

Production assessment activation remains a separate operational gate. The
approved behavior may be implemented and tested with trusted synthetic records
while normal routes remain disabled by server-controlled configuration.
