# Conceptual Data Model, Referential Integrity and Audit Invariants

This is **not** a final SQL schema. MARKADO uses **PostgreSQL** as its centralized relational database, accessed by **Laravel** [DECIDED]. The database host/managed provider, data-residency arrangements, exact school/tenant scope and retention rules remain [OPEN]. There is **no required per-device offline synchronization domain** in the approved online scope.

## Entity catalog [REC]
| Entity | Illustrative fields | Major invariant |
|---|---|---|
| UserAccount | id, email, role, status, created_at | Verified and authorized identity state |
| StudentProfile | user_id, student_number, institution_scope | Student number unique in decided scope |
| StaffProfile | user_id, staff role/assignment | Ownership/scope cannot be inferred by client |
| Quiz | id, owner_id, title, state, join_code, schedule policy | Accessible only when published + eligible |
| QuizRevision | id, quiz_id, revision, immutable settings snapshot | Started attempts point to stable revision |
| Question | id, owner_id, content, type | Stable identity independent of display order |
| QuestionRevision | question_id, version, answer key, points | Review/grading refer to exact version |
| Choice | id, question_revision_id, text | Canonical correctness tied to choice ID |
| QuizQuestion | quiz_revision_id, question_revision_id, ordering | Publishable question belongs to proper snapshot |
| Material | id, uploader, location, type, visibility | Untrusted file; access scoped |
| GenerationJob | id, material_id, state, provider metadata | AI output is never auto approved |
| GeneratedDraft | id, generation_job, review state, source reference | Review transition attributable to human |
| Attempt | id, student_id, quiz_revision_id, start/end/submit, state | Exact allowed attempts and terminal submission |
| AttemptQuestion | attempt_id, question_revision_id, display position | Randomized order reconstructable |
| AttemptChoice | attempt_question_id, choice_id, display position | Stable submitted choice identity |
| Answer | attempt_id, question_revision_id, response, saved_at | No overwrite after terminal state |
| Score | attempt_id, earned/max, grade state | Computed from authoritative snapshot |
| ScoreAdjustment | attempt_id, old/new, reason, actor, timestamp | Append-only change history |
| AuditEvent | actor, action, resource, timestamp, metadata | Tamper-aware record for key operations |
| ExportJob | actor, filters, sort, scope, file locator | Same data restrictions as results view |

## Relationships [REC]
- One UserAccount may have one StudentProfile or StaffProfile (exact multi-role rule [OPEN]).
- One Quiz has many QuizRevisions; each QuizRevision binds fixed QuizQuestions.
- One Attempt belongs to exactly one StudentProfile and one QuizRevision.
- One Attempt has many AttemptQuestions and Answers; answer references stable question IDs.
- One Material may originate many GenerationJobs; each has many GeneratedDraft records.
- Score adjustments are separate from the original attempt/answers.
- ExportJob records what user requested, not a permission to broaden data scope.

## Database constraints [REC]
1. Unique `(institution_id, normalized_student_number)` only **after** institution scope is approved.
2. Unique canonical email per chosen scope, with safe recovery path.
3. Unique code for actively routable quizzes under approved code-reuse policy.
4. Unique `(attempt_id, question_revision_id)` answer where only one response per item is permitted.
5. Unique attempt slot `(student_id, quiz_id, attempt_index)` with attempt policy guard.
6. Foreign keys must prevent orphan answer, score or snapshot records.
7. Server-validated state transitions and transactional score finalization.
8. Append-only audit/adjustment histories except under documented retention/legal rules.

## PostgreSQL-specific integrity patterns [REC; implementation details not yet approved]
- Define foreign keys, unique constraints and check constraints in version-controlled Laravel migrations; application validation alone is not a substitute.
- For student number identity, choose the school/tenant uniqueness scope **first** (D02/D13); then enforce that canonical value using a unique index. Do not implement an across-all-schools uniqueness rule by accident.
- Establish a documented email normalization policy before using a functional unique index, generated canonical column or case-insensitive type. Avoid assuming `email` comparisons are case-insensitive by default.
- Use `timestamptz`/equivalent correctly, store authoritative instants in UTC and convert in the UI to the approved display timezone (D10 still OPEN).
- For attempt finalization, protect a single transition with a database transaction and appropriate row lock/conditional update, and enforce a unique result per attempt as needed. Consider retries and deadlocks.
- PostgreSQL JSONB can store non-authoritative structured metadata where useful; avoid putting all grading rules or relational invariants into an unvalidated JSON blob.
- Name and test indexes against real queries (quiz code lookup, attempt owner/status, roster sorting, student email/number uniqueness, reporting). Avoid speculative indexes before the query shape is known.
- Use per-role database permissions, encrypted connections where applicable and tested backup/restore procedures; host/provider and backup objectives are D28/D19 [OPEN].

## Concurrency [REC]
Use transaction boundaries or equivalent durable locking around new attempts, submission finalization, score calculation and registration uniqueness. An application-level `if not exists then insert` is unsafe without a matching database unique constraint.

## Data minimization [REC]
Don't add birth date, address, government ID, contact list or biometric fields without demonstrated need and permission. Keep upload text and answer keys from unrelated records separate; support a policy-based deletion/retention mechanism.

## Illustrative system invariants [REC]
- An Answer cannot be attached to a question absent from its attempt snapshot.
- An Attempt cannot be marked submitted twice.
- Submitted-at is server trusted; client time is presentation metadata at most.
- An AI draft cannot become a QuizQuestion for publication without approved review state.
- A student's result query can return only their released results.
- PDF/DOCX results must derive from the same filtered authorized report model.

## Event design [REC]
Useful audit events: `student.registered`, `quiz.created`, `quiz.published`, `quiz.revised`, `material.uploaded`, `generation.completed`, `draft.approved`, `attempt.started`, `answer.saved`, `attempt.submitted`, `attempt.expired`, `score.adjusted`, `result.released`, `export.generated` (redact sensitive payloads). Event names are illustrative.
