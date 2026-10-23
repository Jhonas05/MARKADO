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
- **Decision references:** D01-D10, D16, D22, and D25-D27 have approved boundaries across ADR-001 through ADR-006; D24 (offline-only synchronization) is NOT APPLICABLE. Read every applicable file under `../decisions/` and preserve each ADR's explicit exclusions.

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
