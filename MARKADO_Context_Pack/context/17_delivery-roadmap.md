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
