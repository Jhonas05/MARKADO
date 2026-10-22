# MARKADO Complete Development Context - Version 1.2

**Date:** 22 October 2026  
**Approved stack [DECIDED]:** Online web application / **Laravel (PHP)** / **PostgreSQL** / **React + Inertia.js**.  
**Implementation status:** This is a planning requirements/architecture guide, not evidence of an existing deployed system or completed code.

This file combines all 21 `context/` chapters and is convenient for AI assistants requiring a single upload. **F01-F08** are originally requested features; **[DECIDED]** marks user-approved choices; **[REC]** is supplementary implementation guidance; **[OPEN]** requires approval. PostgreSQL and React/Inertia.js were confirmed in a later conversation, not in the original MARKADO worksheet/PDF.

See `decisions/ADR-001-online-laravel.md` and `decisions/ADR-002-postgresql-react-inertia.md` for decision history. The approved stack is not proof that the code is built.

---

<!-- SOURCE: context/00_START_HERE.md -->

# MARKADO - Development Context and Navigation

**Edition:** Expanded planning pack v1.2 - Laravel + PostgreSQL + React/Inertia.js | **Date:** 22 October 2026 | **State:** Requirements and proposals, **not** a claim that features are implemented.

## What is MARKADO?
MARKADO is a digital quiz and assessment management system for students, instructors/professors, and authorized academic administrators. It is intended to handle assessment authoring, secure participation, timed attempts, identity/duplicate controls, AI-assisted draft generation, results, and exports while following HCI principles.

## Read this first: three levels of certainty
- **[REQ] Requested baseline:** F01-F08 originate from the supplied 3-page MARKADO development-context PDF, itself based on a planning worksheet and a feature list.
- **[REC] Design recommendation:** supplemental implementation guidance in this pack, offered for evaluation; it is **not** a user-approved product decision.
- **[DECIDED] User-confirmed architecture:** online web application, Laravel (PHP), PostgreSQL, and React integrated using Inertia.js (confirmed 22 October 2026). These choices do not establish implementation status.
- **[OPEN] Decision required:** policy or architecture choice not yet approved. Do **not** implement a default as if approved.

## Confirmed architecture decisions [DECIDED]
- **Deployment:** MARKADO is a **public-internet-accessible, online web application**. The quiz experience depends on a reachable central application/server; a fully offline, standalone-per-device examination mode is **not in the agreed scope**.
- **Backend:** **Laravel (PHP)** [DECIDED]. The exact Laravel/PHP versions, authentication starter kit, queue transport, storage provider and production host remain [OPEN].
- **Database:** **PostgreSQL** [DECIDED], as the centralized relational database. PostgreSQL service/host and backup design remain [OPEN].
- **Frontend:** **React + Inertia.js** [DECIDED]. Laravel handles server routes and authorization; React renders Inertia pages. TypeScript, styling library, SSR strategy and bundler specifics are recommendations/pending verification, not locked choices.
- **Expected architectural consequence [REC]:** centrally persisted student identities, quizzes, answer saves, deadlines, grades and reports; the server enforces timing and access, while the browser displays state.
- **Connection-loss behavior [OPEN]:** determine precisely what happens when the learner disconnects or a server is unavailable; never imply that unacknowledged work was saved.
- **Decision references:** D01, D25, D26, and D27 are DECIDED; D24 (offline-only synchronization) is NOT APPLICABLE. See `../decisions/ADR-001-online-laravel.md` and `../decisions/ADR-002-postgresql-react-inertia.md`.

## Confirmed requested feature index
| ID | Requested capability | High-level outcome |
|---|---|---|
| F01 | Per-question and whole-quiz timers | Clearly visible and faithfully enforced time limits. |
| F02 | Alphabetical results and score ranking; PDF/Word exports | Repeatable, authorized instructor reporting. |
| F03 | Unique quiz access code and shareable link | Straightforward joining with access validation. |
| F04 | Quiz creation from instructor materials | AI-generated editable drafts with human review. |
| F05 | Date and time quiz availability | Unambiguous start/close windows. |
| F06 | Student number + email registration | Reduce accidental or deliberate duplicate accounts. |
| F07 | Question and answer-choice randomization | Fair randomization without breaking grading. |
| F08 | Accessible Help / Guide | Consistent instruction, support, and recoverable errors. |

## How a development agent should use these files
1. Read `00_START_HERE.md`, `01_product-charter.md`, `02_requirements-and-traceability.md` and `18_open-decisions.md` before architecture or implementation.
2. Identify which changes are [REQ], [REC], or [OPEN]. Never silently resolve an [OPEN].
3. Follow role, workflow, domain model, timer, scoring, privacy, and export invariants in the matching files.
4. Produce an implementation plan with feature IDs, policy assumptions, migration implications, and test IDs.
5. Before release, use QA and security gates. Update the progress tracker with evidence rather than vague "done" status.

## File map
- `01_product-charter.md`: goals, non-goals, users, measurements.
- `02_requirements-and-traceability.md`: requirements F01-F08, child IDs and acceptance checks.
- `03_roles-and-permissions.md`: roles, isolation and permission decisions.
- `04_user-journeys-and-states.md`: author, student and admin paths.
- `05_quiz-authoring-and-publishing.md`: drafts, versions and lifecycle.
- `06_student-registration-and-access.md`: identity, sign-up, join gates.
- `07_timers-schedules-and-attempts.md`: clocks, deadlines and concurrency.
- `08_questions-randomization-and-scoring.md`: grading and ordering.
- `09_AI-question-generation.md`: upload and human-review pipeline.
- `10_results-exports-and-reports.md`: score/rank and PDF/DOCX.
- `11_HCI-UI-UX-specification.md`: screen inventory, copy and accessibility.
- `12_conceptual-data-model.md`: entities, invariants, relationships.
- `13_architecture-and-interfaces.md`: approved Laravel + PostgreSQL + React/Inertia.js architecture and still-open implementation components.
- `14_security-privacy-and-risk.md`: privacy and abuse prevention.
- `15_test-plan-and-QA.md`: automated/manual scenarios and QA gates.
- `16_operations-reliability-and-observability.md`: runtime and recovery.
- `17_delivery-roadmap.md`: phases and definitions of done.
- `18_open-decisions.md`: decided stack and remaining open decision register; see both ADR files under `../decisions/`.
- `19_resources-and-source-notes.md`: original-source boundary and credible references.
- `20_glossary-and-examples.md`: concrete examples and terminology.

## Source boundary
The provided attachment is `MARKADO_Development_Context(1).pdf` (three pages). It reports that a separate "MARKADO CHI Planning Worksheet" existed, but the full worksheet is **not** part of this pack. Its exact contents and bibliography have **not** been independently reconstructed. Review this pack as a design expansion of the available PDF, not as a transcription of the unavailable worksheet.

---

<!-- SOURCE: context/01_product-charter.md -->

# Product Charter, Scope and Success Criteria

## Problem statement [REQ]
Creating quizzes, handing out access details, controlling time, checking student identities, reviewing results, and exporting class reports can require disconnected manual steps. MARKADO proposes a single controlled flow for these tasks with straightforward student interaction.

## Product outcomes [REC]
1. Instructors can create, review, schedule and publish a quiz without needing technical assistance.
2. Students can reliably register, join, understand the rules, complete an attempt, and confirm submission.
3. Academic users can interpret results without ambiguity about ranking, attempt status or release policy.
4. Administrators can support users without unnecessarily obtaining student answers or grades.
5. As an online web application, the product gives truthful connection/save status during time pressure, errors and temporary internet interruptions, subject to an explicit reconnection/outage policy [OPEN].

## Approved platform direction [DECIDED]
MARKADO will be an **online Laravel (PHP) web application** with **PostgreSQL** as its relational database and **React + Inertia.js** for the web UI [DECIDED]. It is not LAN-only or fully offline per device. Users access the centralized application from their browsers. Hosting provider, exact Laravel/PHP and frontend package versions, auth library, queues, file storage, and backup goals are [OPEN].

## Primary audience
| Persona | Needs | Primary risk |
|---|---|---|
| Student | Clear instructions, accurate timer, safe progress, confident submission | Time loss, mistaken submission, login/duplicate confusion |
| Instructor/professor | Efficient quiz construction, predictable scheduling, trusted results | Wrong answer key, wrong access window, inconsistent scoring |
| Academic administrator | Account and assessment oversight with auditable powers | Excessive access, impersonation, unclear support actions |

## In-scope [REQ]
F01-F08 as indexed in `00_START_HERE.md`; role-limited access; integrity-conscious result processing; HCI guidance including understandable errors and Help/Guide.

## Proposals, not baseline commitments [REC]
Question bank; quiz preview; publishing checklist; auto-save; section/course assignment; audit dashboard; version snapshots; bulk student import; anomaly flags; advanced analytics; manual grading; accommodations; course-based dashboards; activity notifications. Each requires explicit prioritization before implementation.

## Explicitly unspecified [OPEN]
Precise supported question formats, live supervision/proctoring, institution/tenant hierarchy, gradebook integrations, instructor approval levels, provider for AI generation, paid services, question-import limits, data-retention term, device compatibility, Laravel/PHP and React/Inertia versions, supplementary UI packages and production hosting.

## Non-goals unless later authorized [REC]
Do not assume a full LMS, transcript authority, school master records, fee collection, remote proctoring/surveillance, biometric identification, or guaranteed cheating detection. A quiz link is a navigation aid, not sufficient proof of eligibility.

## Candidate outcome measures [REC]
| Measure | Practical definition | Measurement method |
|---|---|---|
| Quiz creation completion | Share of authoring sessions that reach an approved publish step | Creation funnel telemetry without exposing answer content |
| First-try join rate | Eligible students joining a published quiz on first valid attempt | Aggregate join outcome events |
| Submission reliability | Accepted submission acknowledgements / valid submit requests | Server events and retry reconciliation |
| Export consistency | Match between filtered screen results and generated file | Deterministic automated test |
| Help effectiveness | Reported ability to complete common tasks without staff intervention | Usability testing and feedback |

## Stakeholders and sign-off [REC]
Identify faculty lead, student tester, product owner, developer lead, privacy/security reviewer and academic policy decision-maker. Record every blocking [OPEN] in the decisions register with an owner. An attractive UI does not replace consent, correctness, usability testing, or grade integrity.

---

<!-- SOURCE: context/02_requirements-and-traceability.md -->

# Feature Requirements, Acceptance Criteria and Traceability

The eight features below are **requested**. Their detailed implementation criteria are **recommended interpretations** of the supplied PDF and should be confirmed against project policy.

## F01 - Time limits
- **F01.1 [REQ]** Instructor/admin configures a limit per question and for the quiz session.
- **F01.2 [REQ]** Student sees remaining time clearly.
- **F01.3 [REC]** The authoritative attempt timer is computed by the trusted assessment runtime, never from client display time alone.
- **F01.4 [REC]** Reload, second tab, clock adjustment, or reconnection cannot grant an extension.
- **F01.5 [OPEN]** Whether both timers run simultaneously, whether expiration locks/advances/submits, and which deadline wins.
- **Accept:** A cross-device refresh preserves deadlines; expired answer mutations are rejected; UI announces time warnings accessibly.

## F02 - Result listing, ranking and export
- **F02.1 [REQ]** Alphabetical list by student name.
- **F02.2 [REQ]** Alternative highest-to-lowest score order.
- **F02.3 [REQ]** Exports to PDF and Word.
- **F02.4 [REC]** Tie breaks and name normalization are deterministic, with stable reference IDs.
- **F02.5 [REC]** Both export formats use the same authorized query, labels, filters and result-release policy.
- **F02.6 [OPEN]** Score basis, handling of multiple attempts, grade adjustments and ties.
- **Accept:** The exported roster reconciles with the selected on-screen roster, including statuses and ties.

## F03 - Quiz access code and link
- **F03.1 [REQ]** Every published quiz has a unique access code and join link.
- **F03.2 [REQ]** Code/link entry feels straightforward like a quiz-access flow used in popular learning products; no third-party dependency is implied.
- **F03.3 [REC]** Access token is non-guessable enough for its threat model, and lookup is rate-limited.
- **F03.4 [REC]** Enforce authentication, publication status, availability and eligibility **after** link resolution.
- **Accept:** Wrong, expired, unpublished, unauthorized and malformed access attempts fail consistently with safe feedback.

## F04 - Question generation from learning materials
- **F04.1 [REQ]** Instructor/admin provides source materials for question generation.
- **F04.2 [REQ]** Generated questions are drafts requiring human approval before publishing.
- **F04.3 [REC]** Preserve source provenance and flag low-confidence output, ambiguous answers and missing evidence.
- **F04.4 [OPEN]** Supported file types, OCR, provider, retention of uploads, token/cost limits, allowed languages.
- **Accept:** Rejected draft never appears in a student attempt; each accepted draft is editable and attributable to its review.

## F05 - Quiz availability by date and time
- **F05.1 [REQ]** Configure opening and closing date/time.
- **F05.2 [REC]** Store times unambiguously and show effective timezone in authoring and student views.
- **F05.3 [OPEN]** Can an attempt already started continue after close? Is the close inclusive/exclusive?
- **Accept:** Boundary tests demonstrate exactly when starting is accepted or denied.

## F06 - Student number and email registration
- **F06.1 [REQ]** Require both student number and email to register.
- **F06.2 [REQ]** Reject duplicates using the chosen institutional uniqueness scope.
- **F06.3 [REC]** Add transactional uniqueness constraints; normalize appropriately; provide a safe recovery path.
- **F06.4 [OPEN]** Who issues/validates a student number; verification requirement; accounts shared across institutions or school years.
- **Accept:** Two near-simultaneous signups with one student number cannot both succeed; recovery does not reveal an account unnecessarily.

## F07 - Randomization
- **F07.1 [REQ]** Randomize order of questions, choices, or both when enabled.
- **F07.2 [REC]** Persist per-attempt permutation/snapshot so scoring and review refer to stable IDs.
- **F07.3 [REC]** Do not shuffle special ordered-question types unless their semantics support it.
- **F07.4 [OPEN]** Randomization scope and instructor exceptions.
- **Accept:** Scoring stays identical under every permutation and the student's actual displayed order can be reconstructed.

## F08 - Help and Guide
- **F08.1 [REQ]** Provide user help/instructions.
- **F08.2 [REC]** Context-sensitive FAQ, first-time onboarding, keyboard navigation and actionable empty/error states.
- **F08.3 [REC]** A help overlay must not pause or unintentionally terminate an active attempt unless approved by policy.
- **Accept:** A student can find time rules and submission steps before entering a timed attempt and open help safely mid-attempt.

## Cross-feature dependencies
| Feature combination | Required design conversation |
|---|---|
| F01 + F05 | Effective attempt deadline vs global quiz closing time |
| F01 + F08 | Timer visibility and whether help consumes attempt time |
| F03 + F06 | Code/link does not override login or identity checks |
| F04 + F07 | Generated question IDs and stable choice IDs for randomization |
| F02 + F07 | Correct score independent of randomized presentation |
| F02 + F06 | Exports identify the right student without unnecessary data exposure |
| F02 + F05 | Late/expired/in-progress records and results release rules |

## Requirement change protocol [REC]
Record original text, source, proposed interpretation, affected roles, data migration risk, QA impact and approver before changing the baseline. Maintain an ID-based requirements-to-tests matrix (`templates/requirements_traceability.csv`).

---

<!-- SOURCE: context/03_roles-and-permissions.md -->

# Users, Roles, Authorization and Boundaries

## Source-supported roles [REQ]
**Student:** register/sign in, access eligible quizzes, read rules, attempt, submit and see results only when released. **Instructor/professor:** own and manage quizzes, materials, settings, review generated drafts, publish and inspect authorized results. **Academic administrator:** authorized account/assessment oversight; exact power set needs approval.

## Proposed authorization matrix [REC]
| Action | Student | Instructor | Academic admin |
|---|---|---|---|
| Register/login | Own account | Own account | Own account |
| See quiz instructions | Eligible quiz only | Owned/assigned | By approved scope |
| Create/edit quiz | No | Owned | If explicitly allowed |
| Publish/close quiz | No | Owned | If explicitly allowed |
| Manage sources and AI drafts | No | Owned | If explicitly allowed |
| Start/answer attempt | Eligible own attempt | No, except preview | No, except controlled QA |
| See scores | Own released score | In scope | Only if authorized |
| Export grades | No | In scope | Only with approved purpose |
| Regrade or override score | No | If explicitly authorized | Requires separate policy |
| View audit events | Own activity subset, if offered | Relevant owned activity | Administrative oversight by scope |

## Scope rules [REC]
Authorization must depend on **actor + action + resource scope**, not role string alone. For example, an instructor may view their Quiz A results but not another faculty member's Quiz B results. Student profile endpoints may return only their own data. Exports are particularly sensitive: test them independently from the UI.

## Avoid privilege ambiguities [OPEN]
- Who creates instructor/admin accounts, and is role verification required?
- Can administrators impersonate users for support? Recommended default: **no direct impersonation** without a separate controlled policy.
- Can a teaching assistant co-own/edit a quiz? How is removal handled?
- Can students enroll in several classes? What institution/course/section identity exists?
- Is the product for one school, several schools, or standalone quizzes?

## Access-control acceptance checks [REC]
- A different instructor changing an object ID cannot see, edit or export another instructor's quiz.
- A student cannot retrieve another student's attempt, answer key, grade or personal profile.
- Role changes take effect consistently on active sessions per approved policy.
- The server authorizes every change independently of whether a button is hidden in the UI.
- Administrative activity such as regrades and overrides records a reason and audit event.

---

<!-- SOURCE: context/04_user-journeys-and-states.md -->

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
- **CLOSED:** new starts disabled; treatment of active attempts is [OPEN].
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

---

<!-- SOURCE: context/05_quiz-authoring-and-publishing.md -->

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

---

<!-- SOURCE: context/06_student-registration-and-access.md -->

# Student Enrollment Identity, Registration and Quiz Joining

## Baseline [REQ]
Student registration requires **student number** and **email address** to reduce duplicate accounts. Code/link provides quiz access navigation, but authentication and authorization must still be evaluated.

## Registration flow [REC]
1. Collect student number + email with clear field labels and privacy notice.
2. Normalize values carefully; e.g. trim permitted whitespace and compare email consistently without assuming that all address local parts can be freely rewritten.
3. Validate format and institutional number rules once defined.
4. Transactionally check unique key(s) in database - front-end validation alone is insufficient.
5. Verify email/student identity only according to approved school policy.
6. Create account and issue a non-enumerating verification/recovery status.
7. Record creation and email verification events where applicable.

## Proposed identity and uniqueness invariants [REC]
- Student number must have a **defined uniqueness scope**: institution, campus, or global [OPEN].
- Email uniqueness must also have a defined scope, particularly if guardian/shared mailboxes are possible [OPEN].
- Server-side unique constraints handle concurrent double submits.
- Do not equate an email with proof that a specific student number belongs to the sender.
- Account correction/merge actions preserve audit evidence; never silently move grades between identities.

## Quiz join gate [REC]
`resolve code/link -> authenticate -> resource scope/eligibility -> quiz published -> availability window -> attempt policy -> instructions -> start`

Do not expose quiz question/answer details to unauthorized link visitors. Distinguish inaccessible link from invalid authentication in user-friendly terms without confirming another person's account existence.

## Example messages [REC]
- "Please enter your student number and school email." (The school-email limitation is [OPEN]; use "email" if not required.)
- "We couldn't complete registration with those details. Check your information or use account recovery."
- "This quiz is not available right now. Check the schedule shown by your instructor."
- "You're already taking this quiz. Continue your existing attempt." (Only when resume is supported and authorized.)

## Cases that need product decisions [OPEN]
Transferred student; two institutions using identical student numbers; one student changing email; instructor registering under student email; typo correction; unverified school records; deleted account later reused; one email shared by siblings; academic-year rollover. These are identity-policy choices, not merely coding errors.

---

<!-- SOURCE: context/07_timers-schedules-and-attempts.md -->

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

---

<!-- SOURCE: context/08_questions-randomization-and-scoring.md -->

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

---

<!-- SOURCE: context/09_AI-question-generation.md -->

# AI-Assisted Question Generation from Materials

## Requested workflow [REQ]
Authorized professor/admin uploads or supplies learning material, requests question generation, inspects editable draft questions and **approves them manually** before publication. This is a drafting assistant, not an autonomous assessment authority.

## Recommended pipeline [REC]
1. **Authorize** uploader against intended quiz/material scope.
2. **Accept and scan** file under approved format, size and malware policy.
3. **Extract** machine-readable text; mark partial extraction and inaccessible figures instead of hallucinating missing context.
4. **Segment** material into attributable source spans (page/section where possible).
5. **Specify** generation target: topic, count, candidate question types, language and difficulty descriptors.
6. **Generate** structured draft objects, not free-text blobs.
7. **Validate** schema, option count, correct answer existence, duplications and source alignment.
8. **Review** with instructor-facing source excerpt, editable answer key and reject/regenerate options.
9. **Approve** selected question revisions with reviewer and timestamp.
10. **Publish** only approved questions through normal quiz checks.

## Example conceptual draft fields [REC]
`generation_job_id`, `material_id`, `source_page_or_section`, `draft_question_id`, `prompt`, `type`, `options`, `proposed_answer`, `explanation`, `review_status`, `reviewer_id`, `reviewed_at`, `revision_id`.

## Safeguards [REC]
- Treat document contents as untrusted **source text**, never as instructions to bypass permissions or publish answers.
- Never send entire student rosters, grades, or unnecessary identifying data to an AI provider.
- Never silently publish unreviewed output, even if model confidence appears high.
- Let instructors reject a question for ambiguity, invented information, wrong answer, grade-level mismatch or duplicate concepts.
- Mark unsupported generation when source is too short, scanned without extraction, unreadable, or irrelevant.
- Keep enough provenance for a human to check, without making unsupported claims about certainty.

## Operational limits [OPEN]
Accepted file types (e.g. PDF, DOCX, text), size/pages, image/OCR scope, number of draft questions, provider, data-transfer and storage approval, source deletion policy, usage quota, expected latency, online provider connectivity, languages, accessibility requirements, and costs.

## Generator evaluation plan [REC]
Sample diverse instructor-authored materials. Score each generated question for factual support, single unambiguous answer, plausible distractors, alignment to level/outcomes, language quality, accessibility and duplicate rate. Require instructor signoff. Track failures to improve prompts and validation, not to claim zero hallucinations.

## Relationship to approved platform [DECIDED + REC]
MARKADO is an **online Laravel web app**. Use **Laravel-managed background jobs** as a recommended pattern for extraction and question-generation work; do not make quiz-taking depend on the AI provider being available. Provider, approval for transmitting uploads, usage budget, queue backend, OCR libraries, retention and timeout/retry rules remain [OPEN]. This is an architectural recommendation, not a declaration that a particular AI service is installed.

---

<!-- SOURCE: context/10_results-exports-and-reports.md -->

# Results, Score Ranking, Alphabetical Rosters and PDF/DOCX Exports

## Requested result views [REQ]
**Alphabetical view:** list students by name. **Score ranking:** sort from highest to lowest score. **Exports:** PDF and Word are both supported output requirements. Permission, release, grading and filtering details must remain consistent.

## Recommended results table [REC]
Columns: student name, student number only if authorized, attempt status, submission timestamp, earned points, total points, percentage if approved, result release state, and instructor action indicators. Avoid disclosing student email by default in exported files unless necessary and approved.

## Deterministic sorts [REC]
- Alphabetical: normalized last name, first name, middle name if present, then stable student ID as tie breaker. Confirm naming conventions before implementation.
- Score order: descending earned score on a common basis, then a documented tie policy (e.g. alphabetical as a stable presentation tie-breaker). **Tied scores remain tied academically**; list-position numbering is not necessarily competition rank.
- If quizzes allow multiple attempts, decide which attempt contributes to the roster [OPEN].

## Results release [OPEN]
Immediate on submission, after close, or manual professor release? Is the student permitted to see only a score, score + item feedback, correct answers, or class rank? Are ungraded essays labeled Pending? These choices govern both on-screen display and API/export security.

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

---

<!-- SOURCE: context/11_HCI-UI-UX-specification.md -->

# HCI, Information Architecture, Screen Requirements and Accessibility

## HCI principles [REQ + REC]
Source goals are understandable navigation, predictable flows, clear feedback, accessible controls, visible timer, progress awareness, responsive layout, validation and recoverability. Operationalize them with: clear system status, recognition rather than recall, error prevention, user control, consistent language, logical keyboard order and accessible feedback.

## Proposed student screen inventory [REC]
| Screen | Key regions | Primary action | Important empty/error state |
|---|---|---|---|
| Registration | student number, email, privacy explanation, recovery link | Create account | Duplicate/recovery without account enumeration |
| Sign in | identity fields, help, errors | Sign in | Failed sign-in and reset guidance |
| Join quiz | code/link entry, optional login guidance | Find quiz | Not found / unavailable / access denied |
| Rules / pre-start | title, schedule, time rules, attempt policy, question count | Start attempt | Not yet open / no eligibility |
| Quiz runner | prompt, choice/input, timer, progress, save state, help | Save/Next | Disconnected/retry, expired question |
| Review answers | question statuses, unanswered warning | Submit | Late/expired policy |
| Submission receipt | server-confirmed attempt reference, timestamp | Return to quizzes | Pending confirmation retry |
| My results | release-state label, grade/feedback if permitted | View result | Pending/not released |
| Help / Guide | searchable topics and task steps | Open relevant guide | Support contact fallback |

## Proposed instructor screen inventory [REC]
Quiz list with Draft/Published/Closed statuses; step-by-step quiz builder; question editor; AI upload/review workspace; schedule/timer editor; student preview; publish confirmation; share code/link view; attempts monitoring; alphabetical/ranked results; export action and export history where retained.

## Online reliability cues [REC]
- Explicitly show **Connected**, **Saving**, **Saved by server**, **Connection lost**, and **Submission confirmed** as distinct UI states where meaningful.
- When the browser cannot reach Laravel, never display "Saved" merely because an answer was typed. Show a safe retry/reconnect action consistent with the approved outage/late-policy rules.
- After reconnecting, reconcile displayed question state, persisted response and remaining time against the server without extending deadlines.
- Do not offer offline examination as a promised feature.

## React + Inertia.js implementation considerations [DECIDED + REC]
The frontend framework **React**, integrated with Laravel using **Inertia.js**, is approved. Recommended UX contracts:
- Keep timer, question navigator, and save-state components reusable and accessible. The countdown is visual only; deadlines come from the server.
- For an Inertia page visit or redirect after save/submit, avoid accidentally resetting React input state or implying an unacknowledged answer was persisted.
- After refresh/back/reconnect, reconcile UI state against the attempt state returned by Laravel; preserved React form state is not the authoritative record.
- Share only safe, actor-authorized Inertia props, including in globally shared page props; no hidden answer key or unreleased grade.
- Loading, disable, error, retry, and final-receipt states need browser QA, not only component snapshots.

## Interaction details [REC]
- **Before Start:** Include quiz title, number of items, total timer, per-question timer rules, attempt count, open/close timestamp **with timezone**, navigation/auto-submit behavior and when scores will be visible.
- **During Attempt:** Keep clock and progress at stable screen positions. Mark current question, answered/unanswered, saved/saving/error, and help without obscure popups.
- **Before Submit:** Make final submission distinct from "Next"; warn on unanswered items; use a confirmation that does not accidentally extend time.
- **After Submit:** Show a receipt based on a server-accepted transition, never merely a local button click.

## Accessible interaction targets [REC]
Aim to assess against **WCAG 2.2 Level AA** where applicable (external recommendation, not a statement of certified conformance). Ensure keyboard access, visible focus, meaningful labels, contrast, adequate targets, form validation descriptions, correct heading order, reduced-motion support and reasonable screen-reader timer announcements. Offer time-related accommodations only once a formal policy exists.

## Responsive behavior [REC]
Desktop: stable timer header, main question area, navigable question map. Mobile: simplified question layout, persistent accessible time signal, reachable primary actions and no horizontally clipped answer content. Do not rely solely on hover, color or desktop-only shortcuts.

## Design tokens suggestion [REC]
Choose a restrained palette, strong text/background contrast, 8-point spacing rhythm, consistent corner radius, and semantic color meaning (information, warning, success, error). Actual MARKADO brand identity is [OPEN]; do not invent official colors/logo. Visual consistency must serve usability more than decoration.

## Microcopy examples [REC]
| Context | Suggested text |
|---|---|
| Quiz unavailable | "This quiz isn't available right now. Check its opening and closing schedule." |
| Timer running | "Time remaining: 12 minutes, 30 seconds." |
| Save pending | "Saving your answer..." |
| Save failed | "Your answer wasn't saved. Try again. Your timer is still running." |
| Submit confirmation | "Submit your final answers? You may not be able to change them afterward." |
| Score withheld | "Your result isn't available yet. Your instructor will release it according to the quiz rules." |
| AI review | "AI-generated question - check the source, answer, and wording before approving." |

## Research and evaluation methods [REC]
A small moderated usability test with students and instructors; task completion and error observation; accessibility audit; mobile/keyboard testing; brief post-task ease survey. Test under realistic time pressure but avoid intentionally stressful or coercive conditions. Document findings and changes before treating HCI goals as achieved.

---

<!-- SOURCE: context/12_conceptual-data-model.md -->

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

---

<!-- SOURCE: context/13_architecture-and-interfaces.md -->

# Approved Laravel + PostgreSQL + React/Inertia.js Architecture

## Platform decision summary [DECIDED - 22 October 2026]
- **D01 Deployment:** online, internet-hosted web application; browser-to-server access is required for accepted quiz operations.
- **D25 Backend:** **Laravel (PHP)** owns authentication, permission checks, quiz workflows, timers, grading, exports and API/application decisions.
- **D26 Database engine:** **PostgreSQL** is the authoritative relational persistence layer. Hosting/instance placement, backups and operational policies are not decided by this choice.
- **D27 Frontend:** **React** integrated with Laravel through **Inertia.js**. Laravel serves page responses and owns routes/data contracts; React renders interactive screens.
- **D24 Scope:** no fully offline per-device exam workflow or eventual offline submission synchronization in V1.

The platform choices are **approved development direction, not evidence that the repo, infrastructure or system is already implemented**. Version numbers, packages, authorization scaffolding, tenancy policy, hosting provider and queue/storage configuration remain unselected.

## Application boundaries [DECIDED + REC]
| Layer | Required shape | Responsibility / caution |
|---|---|---|
| React + Inertia.js | **DECIDED** | Quiz runner, registration, quiz authoring, result views, progress, status and time display; do not keep authoritative grading/security decisions solely in React |
| Laravel PHP | **DECIDED** | Server routes/controllers or actions, validation, auth/policies, transactions, quiz lifecycle, deadlines, permissions and exports |
| PostgreSQL | **DECIDED** | Central persistent relational record: users, quiz/revision, attempts, snapshots, answers, scores, jobs/audit metadata |
| Background queue | **REC; OPEN technology** | Extract materials, process AI question drafts, generate heavy exports without delaying quiz saves |
| Private file storage | **REC; OPEN provider** | Protect learning materials, answer keys and export artifacts; authorized access only |
| Scheduler / expiry sweeper | **REC; OPEN tooling** | Cleanups and overdue finalization; never sole authority for rejecting late saves |
| HTTPS host / domain | **REQUIRED for production; OPEN provider** | TLS, monitoring, deploy/rollback, safe cookies, ops ownership and backup responsibilities |

## Recommended request flow using Inertia.js [REC]
1. Browser navigates to a quiz or staff screen; Laravel authenticates and authorizes the actor, then returns an **Inertia page response** with only safe, actor-scoped props.
2. React renders the screen and manages **ephemeral** UI state, such as selected option before confirmed save, countdown animation, question navigation and unsaved-change warnings.
3. User actions send CSRF-protected requests to Laravel routes; the server validates ownership, quiz schedule, attempt state, authoritative deadline and option/question identity before any write.
4. Laravel transactionally saves an answer or finalizes an attempt in PostgreSQL; the response shows **confirmed** save state, attempt receipt and updated deadline/status. Inertia redirects or partial responses must not accidentally start a new attempt.
5. On refresh or reconnect, React obtains the **persisted** answer and attempt state from Laravel/PostgreSQL; it never resets the initial deadline.
6. Report and export actions use the same server-authorized result model. Inertia props must never expose hidden answer keys or another user's unreleased grades.

**Architectural caution:** Inertia.js is not a replacement for secure server authorization, database transactions, or a live synchronization engine. Avoid assuming real-time web sockets, automatic offline submission, or a dedicated public REST API unless later approved.

## Recommended Laravel module boundaries [REC]
1. **Identity and Authorization:** registration, canonical student number + email, role assignments, policies/gates, safe recovery.
2. **Quiz Authoring and Revisions:** draft/publish rules, stable question/revision/choice identities, human approval of AI drafts.
3. **Quiz Access:** code/link resolution with server-side publication, schedule, eligibility and actor checks.
4. **Attempt Engine:** quiz/question deadlines, randomized per-attempt snapshot, save acknowledgments, atomic/idempotent submit/expiry.
5. **Scoring and Reporting:** grade computation from approved snapshots, release visibility, alphabetic/rank ordering, PDF/DOCX export.
6. **Material/AI Generation:** permission-scoped file access, extraction, job state, generated drafts, reviewer workflow.
7. **Audit and Operations:** security/grade-change logs, exports, failure diagnostics, incident/retry visibility.

## PostgreSQL persistence and correctness contracts [REC]
- Use Laravel schema migrations with PostgreSQL foreign keys and unique indexes as data-integrity backstops. Student-number uniqueness must be scoped only after D02/D13 school identity rules are decided.
- Use an explicit normalization strategy for emails and student numbers; do not assume case-insensitive matching or only rely on Eloquent validation.
- Use one durable server-created `started_at` and deadline set for every attempt; represent authoritative instants with timezone-safe types and test cross-timezone interpretation.
- On simultaneous start/save/submit/expiry operations, use transactions, appropriate row locks or conditional updates, plus idempotency to prevent duplicates.
- Keep published revisions and active attempt question/choice mappings stable. Do not regenerate the random order or answer key on refresh.
- Avoid putting tenant-scoped privacy enforcement solely in React or an unverified default global scope; policies and queries must enforce it server-side.
- Persist audit history separately from mutable score/quiz metadata, with authorized correction and retention policies.

## React + Inertia.js UX and integration contracts [REC]
- Use reusable React components for quiz timer, progress, question controls, confirmation, save status, accessible validation and help.
- Keep the browser clock display aligned with timestamps received from Laravel, but **never** accept browser-reported remaining seconds as authority.
- Model `saving`, `saved by server`, `retry needed`, `expired`, `submitting` and `submitted` separately. Do not mark an answer saved solely on local state change or optimistic success.
- Ensure submission UI guards accidental double-click, but rely on **Laravel/PostgreSQL idempotency** for correctness under retries/races.
- Handle navigation/refresh/back button and Inertia preserved state deliberately, especially for timed attempts; server may refuse changes after deadline even if the UI remains open.
- Avoid including protected answers, administrator-only fields or unreleased scores in shared Inertia props, history/cache, debug logs or error responses.
- Test keyboard access, screen readers, reduced motion, mobile sizes and connection-loss messages in the actual rendered browser.
- TypeScript, Tailwind, component kit, state library, validation library, Inertia SSR and asset build tooling are **not confirmed**. Choose after repo inspection or approval.

## Example interfaces - illustrative, not final URLs [REC]
| Action | Essential contract |
|---|---|
| Join quiz with code/link | Authenticated, permission/eligibility/schedule aware; no hidden answers |
| Start quiz attempt | Transactional and idempotent; returns canonical attempt and fixed deadline |
| Save response | Permission-scoped, validates revision/question/choice, returns server save confirmation or reliable error |
| Final submit | Only one terminal transition, grade/receipt tied to immutable snapshot; retry returns prior receipt |
| Refresh/resume | Restores server-known answers/order and original deadline; never silently grants extra time |
| Instructor results | Teacher-specific authorization and fixed filter/sort semantics |
| PDF/DOCX export | Same authorized dataset and sort; private generated artifacts |
| AI draft approval | Human review, permission + audit, no unchecked auto-publish |

## Verification and release gates [REC]
- Laravel HTTP/feature and policy tests: impersonation, cross-user access, hidden prop leakage, CSRF and quiz-code authorization.
- PostgreSQL tests: unique constraint races, transaction rollback, double submission, concurrent expiry, foreign keys and timezone boundaries.
- React/Inertia tests: countdown/UI state, save/failed-save feedback, navigation/refresh, keyboard/mobile behavior, confirming submission receipt.
- End-to-end tests: online browser -> Laravel -> PostgreSQL; reconnect/retry behavior; exports and queue failure handling.
- Operations: HTTPS, secret isolation, DB restore drills, worker failures and audit log visibility once the actual hosting stack is chosen.

## Choices that remain OPEN
- **D28:** where and how to host Laravel and PostgreSQL; queues/cache, private storage, backups, monitoring, concurrency and recovery goals.
- **D29:** supported Laravel/PHP major versions and exact React/Inertia/library compatibility.
- **Academic/domain policy:** D02-D23 as recorded in the decision register (except D24 out of scope), including timer expiry, result release, access scope, AI provider, data retention and outage accommodations.
- **Optional technology:** TypeScript, styling/UI kit, SSR, cache, email, AI provider, worker technologies and test harness; none should be silently represented as already adopted.

**ADR references:** `decisions/ADR-001-online-laravel.md`, `decisions/ADR-002-postgresql-react-inertia.md`. This document is a planning direction, not implementation evidence.

---

<!-- SOURCE: context/14_security-privacy-and-risk.md -->

# Security, Student Privacy and Assessment Integrity

## Trust boundaries [REC]
Student browsers/devices, instructor browsers, server/API, database, file upload storage, optional AI provider, export/download location, and administrative tools all have different trust levels. Client controls improve UX but cannot be the sole enforcement of permissions, timers or grades when a trusted server is available.

## Security baseline [REC]
- Authentication with secure session handling, credential recovery and reasonable rate limits.
- Authorization at the record and action level for every server endpoint and export.
- Query scoping for owner/class/institution boundaries.
- Robust validation and output encoding to resist malicious text in questions/materials.
- Controlled upload MIME/type/size limits, scanning and safe storage.
- Secrets not embedded in mobile/web client bundles or PDF exports.
- Sensitive data encrypted in transit and appropriate storage protections.
- Audits for access-control changes, publishing, attempts, grade changes and downloads.
- Backups with verified restore tests and access limited to authorized operators.

## Laravel application security checklist [REC]
- Use server-enforced Laravel **policies/gates** (or equivalent tests) for quiz, question, attempt, grade and export ownership. A correct URL or quiz code never bypasses access rules.
- Validate inbound data with Laravel server validation/form requests, but enforce critical uniqueness and idempotency with database constraints/transactions too.
- With **Laravel + React/Inertia.js [DECIDED]**, use secure Laravel session authentication, CSRF protections for form/actions, correct cookie security/same-site settings, and server policies on **every** attempt/result endpoint. Prevent leaked credentials through debug pages and `.env` exposure. Inertia shared props and history must not reveal answer keys or unreleased student grades.
- Keep original teaching material, AI draft sources, exam answer keys and exports in private storage with authorized access and appropriate expiration.
- Limit and log sensitive actions and queue jobs; control file type, file path traversal, upload processing and prompt injection from untrusted content.

## Threat scenarios and mitigations [REC]
| Threat | Potential harm | Candidate mitigation |
|---|---|---|
| Guessing quiz codes | Unauthorized quiz discovery | Sufficient entropy, rate limits, eligibility checks |
| Account enumeration | Revealing student registration | Generic errors, safe recovery, throttling |
| IDOR on attempt/results | Accessing other grades | Server-side resource authorization tests |
| Manipulating browser time | Gaining extra quiz time | Laravel/server-enforced persisted deadlines; countdown is display-only |
| Submit retry race | Duplicate grade / inconsistent state | Transactional state transition + idempotency |
| Answer key leakage | Compromised exam integrity | Server-only answer key, minimal attempt payload |
| Malicious source documents | Prompt injection / malicious files | Isolated extraction, instructions/data separation, scanners |
| Forged admin role | Unauthorized reports | Server role source, scoped permissions, audit |
| Lost export files | Exposure of learner data | Download policy, short-lived links, retention rules |
| Wrong answer from AI | Misgraded students | Human review, provenance and correction workflow |

## Philippine privacy context - review with school [REC]
MARKADO is likely to process identifiable student information and education-related records. Philippine **Republic Act No. 10173 (Data Privacy Act of 2012)** and National Privacy Commission guidance are relevant references; this pack is **not legal advice**. Confirm the school/controller's lawful processing basis, notice, collection, retention, third-party transfer, data-subject rights and incident procedures before rollout. See `19_resources-and-source-notes.md` for official links.

## Retention matrix to approve [OPEN]
| Data | Proposed review questions |
|---|---|
| Student registration | Retain how long after transfer/graduation? |
| Question banks/source uploads | May files include copyrighted third-party content? Who owns them? |
| Raw attempts and answers | What is academic grade appeal/record policy? |
| AI generation requests/output | May the provider store input/output? How long? |
| Audit events | What incident/review retention is justified? |
| Export files | Delete after download or short expiry? |
| Backups | Retention, encryption and deletion propagation? |

## Assessment-integrity policy [REC]
Fairness needs clear start/end rules, reviewer-controlled questions, consistent scoring and supported accommodation choices. Randomization and codes reduce casual misuse but cannot prove academic honesty. Do not claim cheating-proof operation or collect intrusive device telemetry without an explicit policy and demonstrated need.

## Security release gate [REC]
Block production if there are unresolved cross-user grade leaks, bypassable timers, insecure upload execution, leaked answer keys, corrupt retries, or inability to recover data from backup. Use risk acceptance with named owner for noncritical issues.

---

<!-- SOURCE: context/15_test-plan-and-QA.md -->

# Testing, Manual QA, Edge Cases and Acceptance Gates

## Testing layers [REC]
**Unit:** scoring, permutations, normalization, timer/deadline calculations, state transitions. **Integration:** database uniqueness, transactions, data access and export formatting. **Contract:** permission decisions and error types. **End-to-end:** author-publish-student-attempt-results-export. **Usability/accessibility:** student comprehension, keyboard/mobile, timer clarity. **Security:** cross-user access, input validation, files, sessions and grade leakage.

## Test data strategy [REC]
Use synthetic learner identities and grades; do not import real student data into development environments. Test names with Unicode/accent, similar names, duplicate student numbers, common email case variants, long instruction text, large rosters, quizzes with tie scores, attempts close to deadlines and malformed uploaded material.

## Framework-aware verification [REC]
Laravel-specific recommendations: HTTP feature tests for every protected action, policy tests for cross-student/instructor access, database transaction/invariant tests, fake-clock tests for timing boundaries, queue/job retry tests, and deployment smoke tests over HTTPS. With React + Inertia.js selected, add component/browser and end-to-end navigation/refresh/save-feedback tests; with PostgreSQL selected, run constraint, transaction and indexing tests against PostgreSQL, not SQLite-only substitutes.

## Automation suites [REC]
- Property-based randomization and stable answer-ID mapping.
- Time boundaries using fake authoritative clock.
- Concurrent registration with same student number/email.
- Duplicate start and submit, including simulated timeout/retries.
- Score finalization under simultaneous expiry.
- Role/ownership authorization for every endpoint.
- PDF/DOCX export equivalence to on-screen report model.
- Snapshot immutability during a published quiz revision.
- Generated question approval gating.
- Mobile/keyboard accessibility checks where feasible.

## Sample test matrix
| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-001 | Registration | Sign up with valid number + email | Account flow proceeds under chosen verification policy |
| QA-002 | Registration | Repeat existing student number | Uniqueness conflict safely handled |
| QA-003 | Registration | Repeat existing email | Uniqueness conflict safely handled |
| QA-004 | Registration | Two simultaneous registrations same number | Exactly one persistent student identity |
| QA-005 | Registration | Recover mistyped or changed email | Authorized recovery without grade misassignment |
| QA-006 | Join | Enter valid code while eligible | Instructions shown, no unapproved attempt start |
| QA-007 | Join | Invalid code | Safe, usable error |
| QA-008 | Join | Unpublished quiz code | No attempt created/no answers leaked |
| QA-009 | Join | Other instructor's private quiz | Denied despite valid link |
| QA-010 | Join | Unauthorized student | Access denied |
| QA-011 | Availability | Open boundary - 1 millisecond | Not open under defined rule |
| QA-012 | Availability | Exactly at open boundary | Matches documented rule |
| QA-013 | Availability | Exactly at close boundary | Matches documented rule |
| QA-014 | Availability | Timezone conversion | Same instant enforced and displayed clearly |
| QA-015 | Timer | Browser reload | Deadline unchanged |
| QA-016 | Timer | Manual device clock adjustment | Cannot grant more time in trusted-server mode |
| QA-017 | Timer | Question changes | No unapproved timer reset |
| QA-018 | Timer | Whole quiz timer expires | Approved expiry outcome, once |
| QA-019 | Timer | Both timers collide | Policy deterministic and documented |
| QA-020 | Timer | One clock expires during request | Server rule consistent |
| QA-021 | Attempt | Double-click Start | No unintended extra attempt |
| QA-022 | Attempt | Two tabs answering | Single coherent server state |
| QA-023 | Attempt | Refresh during progress | Valid saved answers present |
| QA-024 | Attempt | Drop network before save acknowledgement | No false "saved" UI assertion |
| QA-025 | Attempt | Repeat same answer save | Idempotent or documented coherent behavior |
| QA-026 | Attempt | Submit twice | One terminal submission/receipt |
| QA-027 | Attempt | Submission vs expiry race | Exactly one valid final state |
| QA-028 | Attempt | Submit with unanswered items | Warning and approved behavior |
| QA-029 | Attempt | Save after terminal submit | Modification rejected |
| QA-030 | Question | Shuffle question order | All items included exactly once |
| QA-031 | Question | Shuffle choices | Correct answer preserved via choice ID |
| QA-032 | Question | Wrong/missing answer key | Publish blocked |
| QA-033 | Question | Same option text twice | Validation and intended scoring preserved |
| QA-034 | Question | Long prompt / media | No clipping or inaccessible control |
| QA-035 | AI | Upload allowed material | Job created under owner scope |
| QA-036 | AI | Upload disallowed file type | Safe rejection |
| QA-037 | AI | Generation yields ambiguous question | Reviewer can reject/edit |
| QA-038 | AI | Unapproved draft present | Cannot publish it silently |
| QA-039 | AI | Malicious instructions in source text | Not treated as system instruction |
| QA-040 | AI | Low extraction quality | Clear warning, no fabricated source claim |
| QA-041 | Quiz | Publish with no questions | Blocked with guidance |
| QA-042 | Quiz | Edit after active attempts | Started attempt snapshot unchanged |
| QA-043 | Quiz | Published quiz with invalid schedule | Blocked / policy error |
| QA-044 | Results | Sort alphabetically | Stable expected name order |
| QA-045 | Results | Sort high to low | Correct comparable scoring order |
| QA-046 | Results | Tie scores | Consistent tie handling and stable order |
| QA-047 | Results | Another student's result request | Access denied |
| QA-048 | Results | Result not released | Student sees safe withheld state |
| QA-049 | Reports | Export PDF | Current authorized filter + sort preserved |
| QA-050 | Reports | Export DOCX | Same rows, score values, ordering as PDF |
| QA-051 | Reports | Large class and multi-page PDF | Table header repeat and readable layout |
| QA-052 | Reports | Unauthorized export endpoint | Denied without file disclosure |
| QA-053 | HCI | Keyboard-only quiz completion | All supported actions reachable |
| QA-054 | HCI | Mobile viewport | Timers/questions/actions readable |
| QA-055 | HCI | Help during timed attempt | Help reachable; timer behavior honest |
| QA-056 | HCI | Error or disconnected response | User understands next action |
| QA-057 | Security | Guess resource ID across instructors | Denied |
| QA-058 | Security | Submit malformed choice/question ID | Rejected, no score corruption |
| QA-059 | Security | Inject markup in question text | Rendered safely |
| QA-060 | Operations | Restore simulated backup | Consistent users/quizzes/attempts recovered |

## Online Laravel-specific QA additions [REC]

| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-061 | Connectivity | Disconnect while typing an answer but before server save acknowledgement | Browser never claims unsaved content is saved; clear recovery action appears |
| QA-062 | Connectivity | Reconnect after browser refresh or temporary internet interruption | Laravel returns authoritative answers/state; original persisted deadlines are unchanged |
| QA-063 | Laravel authorization | Student or professor accesses a foreign attempt/quiz via direct URL/HTTP request | Server denies using policy/resource scope; no protected result or answer leaked |
| QA-064 | Laravel validation | Client submits forged score or browser-adjusted remaining time | Server ignores untrusted grade/time and rejects invalid request values |
| QA-065 | Laravel concurrency | Two web requests submit/expire the same attempt simultaneously | Exactly one final attempt transition and grade receipt are persisted |
| QA-066 | Queues and exports | Export job retries and unrelated user guesses generated file URL | No duplicate report disclosure; only authorized requesters can obtain the artifact |
| QA-067 | Expiry safety | Laravel scheduled expiry worker runs late or is temporarily offline | Server rejects late answers/submits under policy even before delayed sweeper catches up |
| QA-068 | Online deployment | Verify HTTPS sessions, CSRF protection, production error handling and secret exposure | Unauthorized browser requests blocked; secure cookies; no credentials or debug trace disclosed |

## PostgreSQL + React/Inertia.js QA additions [REC]

| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-069 | PostgreSQL registration | Parallel requests try to register the same canonical student number or email | Only one account succeeds under the approved uniqueness scope; no duplicate survives |
| QA-070 | PostgreSQL atomic submit | Submit and expiry race across separate Laravel workers and PostgreSQL connections | Exactly one terminal state/grade receipt and no partial transaction |
| QA-071 | PostgreSQL time | Student and instructor browsers use different local timezones | Stored deadline remains same instant and authorized boundaries still enforced |
| QA-072 | Inertia security | Inspect initial and subsequent Inertia page props, shared props and browser history | No answer key or another student's unreleased result is exposed |
| QA-073 | React save status | Disconnect during answer save, then navigate back or refresh | UI does not claim unacknowledged answer saved; server state is recovered safely |
| QA-074 | React countdown | Change browser clock and refresh timed attempt screen | Visual timer re-derives from fixed server deadline; acceptance of late answer denied |
| QA-075 | Inertia form submission | Double-click Submit and retry with a browser refresh or history navigation | One authoritative attempt submission receipt; no duplicate grade |
| QA-076 | Inertia navigation | Navigate among questions with browser back/forward or Inertia visits | Answer UI rehydrates correctly against server state; random order/deadline fixed |

## Manual QA protocol [REC]
For each role, execute happy path then exception path. Capture tester, date/build, feature ID, preconditions, expected outcome, actual outcome, severity, screenshot/trace location and resolution. Every high-risk problem gets a regression test before closure.

## Exit criteria [REC]
- F01-F08 each mapped to passing tests and product approval.
- Zero known critical breaches of identity, authorization, timing, submission or grade correctness.
- Confirmed decisions documented for all [OPEN] items blocking build.
- PDF/DOCX output visually checked, not only string-compared.
- Accessibility/usability test outcomes recorded with follow-ups.
- Backup/recovery and incident-response rehearsal complete for production deployment.

---

<!-- SOURCE: context/16_operations-reliability-and-observability.md -->

# Operational Reliability, Monitoring, Recovery and Support

## Reliability goals [REC]
MARKADO is an assessment system: silent data loss during a timed quiz is more serious than a cosmetic error. Prioritize safe writes, explicit acknowledgement, deterministic deadlines, recoverable incidents and controlled results publication.

## Reliability patterns [REC]
- Persist critical start, save, submit and publish actions atomically.
- Separate "click received" from "commit acknowledged" in student UI.
- Define retry semantics for network failure; use idempotency for irreversible actions.
- Limit heavy jobs such as AI generation and PDF/DOCX exports to background queues when appropriate.
- Treat quiz closing and expiry as policy-bound business events, not just UI timeouts.
- Preserve consistent backups and prove restoration under the chosen hosting model.

## Online Laravel runtime responsibilities [REC]
- Define health checks for Laravel HTTP service, relational database, queue workers, scheduler, and private file storage.
- Use multiple web workers only when session, locking, deadline checks and idempotency behavior are consistent across them.
- Separate long AI and export jobs from interactive quiz saves/submissions; monitor retries and dead-letter/failed jobs.
- During an internet outage, preserve honest **unconfirmed save** and retry UX without implying full offline exam operation.
- Production host, backup cadence, high-availability objectives, monitoring provider and restore owner are [OPEN].

## Health and operational dashboards [REC]
Track errors in auth/join, start acceptance, answer save failures, stale attempt rate, deadline enforcement errors, incomplete submission acknowledgements, AI generation failure rate, export failures, unauthorized access denials and queue backlog. Prefer aggregate metrics that exclude student answer content.

## Incident severity rubric [REC]
| Severity | Example | Operating response |
|---|---|---|
| Critical | Cross-student results exposure; corrupted answer finalization | Stop affected operation, isolate, preserve evidence, communicate via approved process |
| High | Active quiz unable to save/submit; timer systemic mismatch | Escalate, diagnose, apply authorized exam policy |
| Medium | One report export format fails | Preserve result data, retry report job, log incident |
| Low | Misaligned label or nonblocking help copy | Queue UX fix and regression check |

## Support playbooks [REC]
**Cannot join:** confirm authorization, published state, schedule, account and attempt policy without asking for credentials. **Time complaint:** inspect trusted start/deadline and server logs, never grant extensions by editing browser timers. **Uncertain submit:** reconcile authoritative attempt state and return same receipt. **Wrong score:** inspect snapshot/rubric and use approved adjustment path with audit reason. **Missing export:** confirm requester permissions and export job status.

## Recovery and backup [OPEN]
Decide recovery point/time objectives, backup cadence, offsite protection, restore ownership, internet/server outage exam policy and reconnection reconciliation with the **central Laravel server**. Fully offline device-result synchronization is out of scope. Production readiness must include practical drills and rollback routes.

## Avoiding false success indicators [REC]
An HTTP 200 response is not proof of grade integrity. Verify database commit, correct owner scope, attempt status and audit event. Do not expose sensitive raw materials or personal information in analytics dashboards or crash logs.

---

<!-- SOURCE: context/17_delivery-roadmap.md -->

# Implementation Plan, Milestones and Definition of Done

These phases are **recommended sequencing**, not commitments about current implementation status. **Online deployment, Laravel, PostgreSQL and React/Inertia.js are user-approved [DECIDED]**; hosting and exact package versions are still [OPEN]. Dependencies may shift after remaining decisions.

## Phase 0 - Policy and research gate [REC]
- Record D01 online web deployment, D25 Laravel, D26 PostgreSQL and D27 React/Inertia.js as **DECIDED**; resolve timezone, timer/close semantics, identity scope, supported question formats and release policy.
- Confirm intended school/tenant and student eligibility model.
- Decide exact Laravel/PHP and React/Inertia versions (D29), production hosting/managed PostgreSQL/queues/storage/backups (D28), and any optional UI libraries, after inspecting the existing repository if there is one.
- Interview students and instructors; prototype timed flow before building.
- Define privacy responsibilities and nonfunctional targets.
- Exit: approved decision register for all architecture-blocking choices.

## Phase 1 - Identity, quiz access and core skeleton [REC]
- Establish the approved Laravel + React/Inertia.js app skeleton, PostgreSQL connectivity, environment separation, migrations, DB constraints, secure Inertia props, policies and test tooling.
- Implement student registration (student number + email), staff roles and resource authorization.
- Create draft quiz lifecycle and access code/link lookup with publish/eligibility gating.
- Add structured audit events and deterministic error contract.
- Exit: role/registration/join tests pass; no cross-user access.

## Phase 2 - Quiz authoring and attempt engine [REC]
- Editor for V1 approved question types and answer keys.
- Timers and schedule; authoritative **Laravel/server-enforced** persisted deadlines and truthful browser countdown/reconnection behavior.
- Stable attempt snapshots, question/choice randomization and robust submissions.
- Exit: high-risk clock, concurrency and scoring tests pass.

## Phase 3 - Results and reporting [REC]
- Alphabetical and descending-score tables with defined tie policy.
- Result release settings and authorized student view.
- Shared report model producing matching PDF and DOCX exports.
- Exit: export equivalence tests pass on large/messy roster cases.

## Phase 4 - AI draft workflow [REC]
- Allowed material ingestion, text extraction, structured generation jobs.
- Reviewer queue, source references, edit/reject/approve and publish gating.
- Exit: no unapproved question reaches live quiz; provenance and failure handling tested.

## Phase 5 - HCI polish and release hardening [REC]
- Help/Guide and first-time onboarding.
- Keyboard/mobile accessibility, empty states, error clarity and moderated usability test.
- Security review, backup restore, incident handling and production readiness sign-off.
- Exit: requirements traceability and manual QA evidence complete.

## Definition of ready [REC]
A work item has an owner, requirement link, accepted policy, user acceptance criteria, permission scope, data impact, tests and design impact. Features contingent on [OPEN] decisions remain blocked, not "in progress".

## Definition of done [REC]
Implemented with documented API/DB behavior, passing automated tests, role/permission review, manual QA where visual or interactive, audit where material, accessible copy, error recovery and updated progress/traceability. "Code merged" does not mean "released".

## Suggested work item template [REC]
`Feature ID -> Problem -> Actor -> Preconditions -> Happy path -> Exception path -> Authorization -> Data changes -> Accessibility -> Tests -> Approval evidence -> Rollback risk`.

---

<!-- SOURCE: context/18_open-decisions.md -->

# Decision Register - Confirmed Architecture and Remaining Open Choices

## How to interpret this register
The source PDF left deployment and all implementation technologies unspecified. On **22 October 2026**, the user confirmed an **online internet-hosted web app**, **Laravel (PHP)**, **PostgreSQL** and **React (Inertia.js)**. These are documented product architecture decisions, not implementation claims. Every other OPEN item requires product/technical/academic review, especially before implementing grading or time policies.

## Confirmed decisions [DECIDED]
- **D01:** Internet-hosted online web application. Students/instructors/admins access a central server over the internet. No LAN-only or fully offline per-device examination workflow is in current scope.
- **D25:** Laravel (PHP) is the backend/application framework; exact Laravel/PHP version remains D29 [OPEN].
- **D26:** PostgreSQL is the relational database engine; service hosting, backups and placement remain D28 [OPEN].
- **D27:** React with Inertia.js is the frontend integration; versions, design system and optional tooling remain [OPEN].
- **D24 [NOT_APPLICABLE]:** Local isolated-device data synchronization is not required for this online deployment. Temporary internet outages still need an explicit reconnection policy under D09.

## Decision register
| ID | Decision / question | Status | Owner or reviewer |
|---|---|---|---|
| D01 | Online internet-hosted web application; not LAN-only or fully offline per-device | **DECIDED** | Product (user) |
| D02 | Single school vs multiple institutions/sections | **OPEN** | To assign |
| D03 | V1 question formats | **OPEN** | To assign |
| D04 | Per-question and whole-quiz timer interaction | **OPEN** | To assign |
| D05 | Behavior on per-question expiry | **OPEN** | To assign |
| D06 | Quiz-time expiry behavior | **OPEN** | To assign |
| D07 | In-progress attempts when quiz closes | **OPEN** | To assign |
| D08 | Allowed attempts and resume rules | **OPEN** | To assign |
| D09 | Late saves, outage and reconnection policy | **OPEN** | To assign |
| D10 | Timezone standard and open/close semantics | **OPEN** | To assign |
| D11 | Result release timing and answer feedback | **OPEN** | To assign |
| D12 | Ranking/scoring/tie and repeat-attempt aggregation rules | **OPEN** | To assign |
| D13 | Student number issuance and verification | **OPEN** | To assign |
| D14 | Email verification and duplicate recovery | **OPEN** | To assign |
| D15 | Instructor/administrator privileges | **OPEN** | To assign |
| D16 | Live quiz edits, revision handling and unpublish | **OPEN** | To assign |
| D17 | Uploads: file types, OCR and size limits | **OPEN** | To assign |
| D18 | AI provider and upload-processing permissions | **OPEN** | To assign |
| D19 | Retention/export expiry and backup periods | **OPEN** | To assign |
| D20 | Learning material ownership and copyright | **OPEN** | To assign |
| D21 | Supported devices/browsers/accessibility level | **OPEN** | To assign |
| D22 | Student class/section quiz eligibility | **OPEN** | To assign |
| D23 | Manual grading, regrading and appeal permissions | **OPEN** | To assign |
| D24 | Per-device offline synchronization (out of current scope) | **NOT_APPLICABLE** | Product (scope) |
| D25 | Laravel PHP application/backend framework | **DECIDED** | Product (user) |
| D26 | PostgreSQL database engine (host/location tracked in D28) | **DECIDED** | Product (user) |
| D27 | React frontend integrated via Inertia.js | **DECIDED** | Product (user) |
| D28 | Production hosting, queues, storage and recovery goals | **OPEN** | Technical + operations |
| D29 | Laravel version/PHP runtime and dependency support | **OPEN** | Technical lead |

## Architecture-dependent blockers [OPEN]
- **Platform:** PostgreSQL (D26) and React/Inertia.js (D27) are **DECIDED**. D28 hosting/managed PostgreSQL/queues/storage/backups and D29 Laravel/PHP plus React/Inertia dependency versions remain OPEN. Do not silently swap the database/frontend or infer Railway, AWS, Supabase, Tailwind, TypeScript or any provider/package.
- **Academic policy:** D03-D12 and D23 determine core scoring, deadline and result behavior. Laravel implementation must be policy-neutral until approved.
- **Identity/privacy:** D02, D13-D15, D19-D22 affect access and records handling.
- **AI/material workflow:** D17, D18 and D20 must be settled before transferring educational source material to any service.

## Decision record template [REC]
**ADR-ID / Title:** ...  
**Date / approver / status:** Proposed | Decided | Rejected | Superseded  
**Source trigger:** Fxx or Dxx  
**Options evaluated:** at least two meaningful alternatives when appropriate  
**Decision:** precise operational rule, not a slogan  
**Why:** trade-offs and constraints  
**Consequences:** schema, UX, tests, privacy, migrations and failure modes  
**Validation:** acceptance tests and approval evidence  
**Revisit criteria:** change that warrants reconsideration

## Sample unresolved deadline policy - D07
**Question:** A quiz closes at 10:00; student started a 20-minute attempt at 09:50. Does the attempt continue after 10:00? **Option A:** deny that start; **Option B:** allow until 10:10; **Option C:** auto-end at 10:00. **Status: OPEN.** Academic lead must choose and document UI messages/tests before implementation.

## Meeting agenda [REC]
**Do not re-open D01/D25/D26/D27 just by guessing alternatives.** First choose D28/D29 for the Laravel setup and D02/D13/D14 for registration. Next settle D03-D12 for the attempt engine, D15/D16/D22/D23 for permissions and live edits, D17/D18/D20 for uploaded learning materials/AI, D19 for retention and D21 for HCI. Record named approver/date for each new decision.

---

<!-- SOURCE: context/19_resources-and-source-notes.md -->

# Sources, External Guidance and Research Notes

## Authoritative source used for requested requirements
The user-provided **MARKADO_Development_Context(1).pdf**, 3 pages, dated 22 October 2026. Its eight requested features (F01-F08), roles, HCI priorities, broad workflows and unresolved choices are treated as the baseline for this pack.

The PDF cites a previous "MARKADO CHI Planning Worksheet" and mentions Nielsen (1994), Kurdi et al. (2020), and Roediger & Karpicke (2006). The **full worksheet and complete bibliographic entries were not supplied here**. Do not fabricate detailed page references, reported usability test outcomes, or additional findings from those works. Retrieve original sources if formal academic citation is required.

## Verified official supplemental references (consulted 22 Oct 2026)
1. **W3C, Web Content Accessibility Guidelines (WCAG) 2.2:** https://www.w3.org/TR/WCAG22/ - practical accessibility standard for interface and testing targets. WCAG 2.2 AA is suggested as a design target only; this pack does not certify conformance.
2. **OWASP, Application Security Verification Standard (ASVS):** https://asvs.owasp.org/ - security requirement and verification reference (official site identifies stable ASVS 5.0.0).
3. **National Privacy Commission, Republic Act No. 10173:** https://privacy.gov.ph/DATA-PRIVACY-ACT/ - official Philippine Data Privacy Act text.
4. **National Privacy Commission, Data Subject Rights:** https://privacy.gov.ph/data-subject-rights/ - overview of rights for personal-data processing.
5. **UNESCO, Guidance for generative AI in education and research:** https://www.unesco.org/en/articles/guidance-generative-ai-education-and-research - human-centred educational AI governance and validation guidance.

## What these references do and do not establish
These references support **suggested practices**: inclusive interfaces, secure development, data-protection planning and human oversight of generated educational material. They do **not** decide MARKADO's grading, enrollment, admin power or quiz deadline policy. **Online deployment, Laravel, PostgreSQL and React/Inertia.js were confirmed by the user on 22 October 2026**, not inferred from these sources or the original PDF.

## Suggested additional student HCI research [REC]
- Observe how easily students find a quiz link/code and understand pre-start rules.
- Measure whether participants distinguish a saved answer from an actual submission.
- Check how the timer affects ability to navigate and ask for help.
- Ask instructors to create and proofread an AI-assisted quiz, recording ambiguous output.
- Validate that alphabetical/ranked reports are interpreted correctly and consistently.
- Document consent/appropriate participation for any real-person user test.

## Citation handling note
This is a design working pack, **not a scholarly literature review** or a legal opinion. For academic submission, locate and verify complete citations from the original CHI worksheet before including them in final references. Avoid asserting a published research claim solely because the seed document listed a name/year.

---

<!-- SOURCE: context/20_glossary-and-examples.md -->

# Glossary, Concrete Examples and Discussion Prompts

## Glossary
- **Quiz:** instructor-authored assessment with policy, questions, schedule and results.
- **Quiz revision:** fixed version of quiz questions and settings used for attempt consistency.
- **Quiz code:** unique join identifier; not proof of user permission.
- **Availability window:** global dates/times when quiz access may be allowed.
- **Attempt:** a student's individual session for a specific quiz/revision.
- **Per-question timer:** time associated with the current individual question.
- **Session timer:** overall attempt duration.
- **Answer save:** accepted persistence of one response, distinct from final submission.
- **Submission:** terminal transition of an attempt for grading/review.
- **Ranked results:** ordered high-to-low score table under a documented tie policy.
- **AI draft:** generated question requiring instructor inspection and approval.
- **Audit event:** durable actor/action/time/resource record for accountability.
- **HCI:** Human-Computer Interaction; interaction quality, clarity and accessibility.
- **Idempotency:** retrying a request does not produce unwanted additional effects.
- **Authoritative clock:** trusted source of time for deadline enforcement when deployment supports one.

## Scenario A - first-time student [REC]
Student receives quiz code `AB7C9K` (fictional example). On join, the system requests login, validates eligibility, displays instructions with open/close schedule and time rules, and starts only after the student explicitly confirms. The code alone never reveals hidden questions or the answer key.

## Scenario B - instructor with AI source [REC]
Professor uploads a course handout, requests five draft questions, reviews source references and edits two ambiguous options, rejects one question, and approves four. The quiz cannot silently publish the rejected or unreviewed item.

## Scenario C - ranking tie [REC]
A class roster has Bea (8/10), Carlo (8/10), Aya (9/10). Descending-score order presents Aya first, then Bea/Carlo under the approved tie display rule. Their academic score tie remains a tie, even if the visible list has distinct row numbers.

## Scenario D - two requests at once [REC]
A student taps Submit as the timer expires. The server handles submission and expiry atomically and records one final state. UI refresh must show the same authoritative result rather than producing two score rows.

## Scenario E - internet connection drops during an online quiz [REC; outage rules OPEN]
MARKADO runs online with Laravel and a central server [DECIDED]. If a learner loses internet access while typing an answer, the interface must not label that answer "Saved" unless Laravel acknowledged persistence. On reconnect, load the authoritative attempt state and original deadlines. Whether any late-save grace policy applies remains D09 [OPEN]; a fully offline-device examination mode is **not** part of the chosen scope.

## Discussion prompts for CHI presentation [REC]
1. How do visible progress and timer feedback reduce uncertainty while respecting accessibility?
2. Why does "student number + email" only reduce duplicates rather than prove identity?
3. How does explicit quiz scheduling help instructors and learners avoid ambiguity?
4. What makes editable AI drafts safer than unattended publication?
5. Why is preserving a student's exact question order important when choices are randomized?
6. How do plain-language errors and Help/Guide affect successful task completion?
7. What does a class result export disclose, and who should be allowed to download it?
8. Which of the open decisions should academic stakeholders approve first?

## Final note
Use this pack to **ask sharper questions and implement approved answers**. It is a development context, not evidence that code, database, security controls or test runs already exist.

---
