# Quiz Authoring, Validation, Publication and Revisions

## Authoring form contract [REC]
Suggested fields: internal quiz ID, title, short instructions, owner, visibility/eligibility, optional class context, question list, point totals, duration, per-question duration, schedule, timezone, randomization flags, results-release choice, lifecycle status and creation/update timestamps. Only required capabilities from the source are mandatory: title/field specifics beyond feature semantics need approval.

## Guided creation steps [REC]
**Step 1 - Identity:** title, instructions and intended learners. **Step 2 - Questions:** add manually or review AI drafts. **Step 3 - Rules:** timers, availability, allowed attempt count, navigation, result visibility. **Step 4 - Review:** student preview and validations. **Step 5 - Publish:** generate join code/link, show success receipt, permit controlled sharing.

## Publish-time validations [REC]
- At least one publishable question and a valid question type/scoring policy.
- Correct-answer/choice references exist and question weights are valid.
- No unreviewed AI-generated question is silently included.
- Start/end window is coherent; timezone is shown.
- Timers and question navigation have internally consistent policy.
- Student-facing instructions accurately describe time, scoring, navigation and release.
- Publisher is authorized within resource scope.

## Published content safety [REC]
Take a **quiz revision snapshot** at publish or attempt start (precise strategy [OPEN]). Store stable question IDs, answer key version, scoring rubric and settings used by each attempt. If a professor edits a published quiz, protect active attempts from silently changing questions or deadlines. Support explicit future revisions rather than mutating already-attempted question meaning.

## Question bank [REC - optional]
If later approved, a reusable bank should separate question identity, revision, tags, source ownership, answer correctness, and usage permissions. Prevent another faculty member from accessing private questions by guessing IDs. Do not add complexity to V1 without proof of need.

## Publish / unpublish / delete policies [OPEN]
- May quizzes be edited while live? Distinguish typo fixes from correctness-altering changes.
- Is unpublishing available after a student starts?
- Can an instructor duplicate a quiz and preserve questions safely?
- Is deletion soft, archived, or prohibited once results exist?
- Is publishing immediate, scheduled, or subject to administrator approval?

## UI acceptance examples [REC]
- The Publish button is unavailable with a precise actionable validation summary.
- Instructor preview shows exactly how timers and choice randomization **will behave**, or clearly labels an approximation.
- Every published version has a unique revision identifier and auditable author/time.
- A live quiz change cannot overwrite a started student's attempt snapshot.
