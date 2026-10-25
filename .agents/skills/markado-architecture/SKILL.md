---
name: markado-architecture
description: Review, clarify, protect, or evolve MARKADO's online Laravel/PostgreSQL/React-Inertia architecture and academic-assessment contracts. Use for architecture reviews, ADRs, security and role boundaries, database and migration decisions, timers, identity, quiz publishing, scoring, UI integration, delivery phases, and design-versus-implementation audits. This is a design/review skill, not implicit authorization to merge, migrate, activate exams, or deploy.
---

# MARKADO — Architecture Skill

## Mission

Keep **MARKADO**, the single-school, internet-hosted academic quiz and assessment application, **secure, coherent, accessible, auditable, and actually usable** by Students, Professors/Instructors, and authorized Academic Administrators. Make the smallest sound architectural decision that preserves academic integrity and gets the Laravel/PostgreSQL/React-Inertia application closer to a verified, complete workflow.

**Do not confuse the root React/Vite prototype with the authoritative Laravel/Inertia application.** A simulated Student/Professor/Administrator screen, a passing SQLite test, a green branch CI run, or an approved policy document is not proof of live institutional readiness.

## Authority and activation

1. Check for repository-root `AGENTS.md`; read it if present. If absent, use `MARKADO_Context_Pack/AGENT_START_PROMPT.md` plus `MARKADO_Context_Pack/context/00_START_HERE.md`. Do not invent an `AGENTS.md`, and flag conflicting or stale instructions rather than silently treating them as current.
2. Inspect the **actual** repository branch, HEAD, working tree, remote `main`, migrations, application routes, manifests and GitHub PR/CI status before claiming an implementation or merge state. Never assume a prior report remains current.
3. Read `MARKADO_Context_Pack/context/02_requirements-and-traceability.md`, `context/13_architecture-and-interfaces.md`, `context/18_open-decisions.md`, relevant domain chapters, applicable `decisions/ADR-*.md`, and relevant `docs/` files. Prefer the newest approved ADR for a superseded policy; retain historical context.
4. Distinguish **[REQ] requested scope**, **[DECIDED]/approved ADR**, **[REC] recommendation**, and **[OPEN] unresolved policy**. Approval of a narrow direction does not approve every implementation mechanism or exception.
5. Rank evidence appropriately: **current explicit product-owner instruction and approved decisions** determine permissible behavior; **current code, schemas, routes, tests and observed results** determine what exists and works. If these disagree, surface the discrepancy. Old bootstrap text and prototype copy can be stale.
6. Use this skill for architecture, policy, interface, design, integration-readiness and phase decisions. It does **not** itself authorize code changes, GitHub merges, database migrations, account activation, exam activation, or production deployment. Execute actions only within the user's explicit authorization.
7. Re-read decisions before preparing implementation prompts for Codex. Preserve authorizations exactly; consent to one PR's merge does not authorize any other PR or operations on `markado.public`.

## Frozen baseline — do not silently replace

| Responsibility | MARKADO choice / observed baseline | Guardrail |
| --- | --- | --- |
| Delivery model | **Online, centrally served, single-school** V1 | No fully offline exam-taking/synchronization or multi-tenant SaaS without a new decision. |
| Backend | **Laravel (PHP)** in `server/` | Laravel owns auth, routing, policies, deadlines, saves, grading and results. Do not switch to FastAPI, Node/Express or another backend without approval. |
| Database | **PostgreSQL**; local PostgreSQL **17** used in verified tests | Authoritative records and constraints live in PostgreSQL. Do not replace with MySQL/Firebase/Supabase; SQLite is regression support only. Production hosting and exact runtime support remain subject to D28/D29. |
| Frontend | **React + Inertia.js**, built with Vite | Laravel-served Inertia screens are the real app. The root `src/` React/Vite project contains legacy/prototype material; do not confuse its fake data with production. |
| Visual system | **CampusNav-derived MARKADO tokens/components**, light and dark | Preserve verified typography, palette, accessibility and responsive behavior. Do not revert to Ink & Marker as the production style or claim pixel fidelity without rendered checks. |
| Roles | Student, Instructor/Professor, Academic Administrator | Server-stored role and active status determine access. Admin rights are permission-scoped, not universal. |
| Questions | V1 single-answer **MCQ** and **True/False** | Other formats, partial credit and weighted scoring are outside current V1 approval. |
| Publication | Immutable published revisions; editable successor drafts | Existing attempts retain their revision, snapshot and key. Unpublish blocks new starts; other effects need separately approved semantics. |
| Quiz eligibility | Trusted school identity plus active direct/section assignment | Quiz code is a locator, **not** an authorization credential. Fail closed for absent/stale/unverified eligibility. |
| Student onboarding | **School-issued institutional email only**, verified and matched to official roster + student number (**approved direction**) | Actual school domain, registrar/SIS authority, self-registration, token lifetime, recovery and privileged account provisioning are **not** thereby approved. Check whether ADR-008 is actually integrated; a draft outside the repo is not a merged ADR. |
| Timer/attempt engine | One resumable attempt, persisted UTC deadlines, server-controlled transitions | Hard close and earliest applicable deadline apply. Browser countdowns cannot alter time. |
| Results | Objective 1/0 scoring; manual Professor release of **earned/maximum** only | Hide keys, item correctness, unreleased scores and other students' results. Ranking/tie policy and exports are not implicitly approved. |
| AI question drafting | Human-reviewed editable drafts; provider/integration still decision-dependent | No automatic publication of generated answer keys or new AI provider without applicable approval. |
| Deployment | Production hosting, HTTPS, scheduler ownership, backups and recovery are separate gates | No automatic cloud provider selection, rollout or live exams just because build/CI passes. |

Avoid adding Next.js, Tailwind, TypeScript migrations, Redis, queue vendors, S3, external identity services, AI providers or other technologies just for fashion. A version appearing in `package.json` is **observed code**, not a blanket product approval.

## Critical architecture invariants

- **Server-authoritative time:** Laravel/PostgreSQL decides availability, question expiry, session expiry, global close, late writes and terminal state. Store timezone-safe UTC instants; display school dates/times in `Asia/Manila`.
- **Approved Start window:** `opens_at <= server_now < closes_at`. An eligible Student may start shortly before close even when the configured session would otherwise run beyond it.
- **Attempt cap:** with a whole-quiz duration, `effective_attempt_deadline = min(started_at + duration, quiz_closes_at)`; without one, global close still caps the attempt. The current question deadline cannot exceed the effective attempt deadline.
- **Question transition:** per-question expiry locks that question, retains its last persisted answer, and advances once. No backtracking in per-question timing mode. Expiry of the **final question** automatically finalizes the attempt; no new grace period or timer reset.
- **One attempt per Student/quiz:** Start is idempotent; resume returns the same attempt, published revision, stable question/choice order, saved answers and original deadlines across refresh, tabs, devices and reconnects. No Student self-reset.
- **Confirmed answer persistence:** label an answer `Saved` only after an authoritative server acknowledgement or reconciliation. Use stable idempotency keys and expected versions; reject stale, foreign, locked and late updates. Connection loss never extends time or promises offline submission.
- **Single terminal outcome:** voluntary submission, final-question expiry, session expiry and global hard close converge under PostgreSQL transaction/locking rules to one receipt and immutable terminal answers. Determine the earliest logical terminal event **by persisted timestamps**, not by delayed worker execution order.
- **Immutable grading:** correct MCQ/True-False = 1 integer point; wrong/unanswered = 0; no weights, penalties, partial credit, percentage grade or leaderboard by default. Grade against the immutable attempt snapshot; later publication revisions cannot change past results.
- **Manual result visibility:** only the authorized owning Professor releases a scored result. Until release, no earned points leak; afterward Student sees earned/maximum points only. Correct answers and item-level feedback stay server-private.
- **Resource authorization, not just role checks:** own attempt for Student, owned quiz/result for Instructor, named and scoped privileges for Admin. Revoked, disabled or pending accounts lose protected access on their next request. No role selector can grant powers.
- **Institutional identity is fail-closed:** an allowed email domain or submitted student number alone is never proof of roster identity. Pending D13/D14 and D15 details prevent unrestricted self-registration, identity marking or privileged roster mutation.
- **Data minimization:** no answer keys, foreign attempt data, protected questions, passwords, tokens, unapproved scores or unnecessary institutional emails in Student/Inertia shared props, logs, exports or error messages.
- **Assessment activation defaults stay OFF:** `MARKADO_ASSESSMENT_ROUTES_ENABLED=false` and `MARKADO_ASSESSMENT_EXPIRATION_ENABLED=false` outside expressly controlled test environments until separate operational approval.
- **Development database is protected:** existing local `markado.public`, ignored `server/.env`, existing branches and user worktrees must not be migrated, reset, seeded, overwritten or cleaned up without explicit permission. Use proved-isolated disposable PostgreSQL schemas for tests.
- **Evidence is scoped:** distinguish UI prototype, implemented but route-gated service, isolated test PASS, exact candidate CI PASS, visual QA, staging activation and production live operation.

## Decision map — always verify latest status

| Decision IDs | Current V1 policy boundary |
| --- | --- |
| D01/D02/D24-D27 | Online, single-school, Laravel, PostgreSQL, React/Inertia decided; no full offline exam. |
| D03 | Single-answer MCQ and True/False only. |
| D04-D06 | Dual timers, question locking/advancement, final-question submission and session expiry decided. |
| D07/D10 | Hard global close, opening inclusive and closing exclusive, UTC persistence/Asia-Manila display decided. |
| D08/D09 | One resumable attempt, no Student reset, no reconnection grace, server-acknowledged saves; exceptional institutional remedies still open. |
| D11/D12 | 1/0 integer scoring; Professor manually releases score/total only. Bulk/revoke release, ranks/ties, exports, overrides remain outside approval. |
| D13/D14 | Institutional email only + official student number/roster association approved **as a direction**; domain(s), issuance authority, verification mechanism, recovery and duplicates still need decisions. |
| D15/D23 | Limited permission-based Admin principle; exact grants, resets, accommodations, regrading and appeals not approved. |
| D16/D22 | Immutable publication and verified school-managed direct/section eligibility decided. |
| D17-D21/D28-D29 | AI/materials/privacy/accessibility and production operations/runtime details retain any unresolved portions in the current register. |

The ADR files are the policy source of truth **for exactly their approved scope**. Where the user has made a newer decision that is not yet in Git, record it as a *confirmed but not-yet-integrated decision*, not an existing committed ADR.

## Architecture-review procedure

### 1. Establish the real baseline

- Record actual local branch/HEAD/dirty state, remote `main`, open PRs, CI status and merge ancestry when relevant.
- Inspect `server/routes/web.php`, `server/routes/console.php`, `server/config/assessment.php`, relevant Laravel models/services/policies/migrations, React/Inertia pages and dependency manifests for the requested feature.
- Identify which pages are legacy prototype/synthetic, which backend services are implemented, which routes are default-gated and which database schema contains the feature.
- Check recent feature tests and the exact GitHub Actions commit; never equate a historical green run with a new integration tree.

### 2. Map the whole affected flow

For assessment changes, examine:

`Login/account status -> trusted roster + section/quiz eligibility -> published revision -> preflight -> Start/resume -> immutable snapshot -> timed answer save -> submit/expire -> objective score -> owning-Professor release -> minimal Student result`.

For security and account changes, examine:

`institutional roster authority -> student-number/email binding -> mailbox verification -> account status/role -> permissions -> session revocation -> quiz access`.

Trace ownership, schema constraints, Inertia payloads, idempotency, race conditions, audit data, error states, jobs, and recovery paths. Identify the **first boundary where an unapproved rule would be required**.

### 3. Make the smallest viable decision

- Compare **current implementation**, **proposed change**, and **safe staged fallback** on academic correctness, privacy, usability, test cost, migration risk and delivery effort.
- Prefer reuse of existing Laravel services, PostgreSQL constraints and CampusNav design tokens over duplicate engines or wholesale rewrites.
- Explicitly name what is excluded: simulated export/AI, policy-dependent identity operations, disabled routes, unavailable visual QA, and unapproved production activation.
- Do not turn a review request into broad refactoring or a new PR unless asked.

### 4. Freeze only necessary contracts

When useful, specify bounded API/data contracts rather than speculative new schemas:

- **Start/resume:** actor, quiz, immutable revision, attempt ID, persisted deadlines, current position, lock/version.
- **Answer save:** attempt + question snapshot ID, stable choice ID, expected version, idempotency key; response includes persisted version/timestamp.
- **Finalization:** attempt, logical event/reason/timestamp, processing timestamp, stable receipt; one locked terminal transition.
- **Result:** attempt + scoring algorithm version + earned/maximum points + release actor/time; never expose grading key in Student projections.
- **Identity:** roster reference, school student number, institutional email, separately verified mailbox and roster-match states, actor/authority, safe audit trail.

These are **contract descriptions**, not permission to claim a route, endpoint, column or event already exists. Verify real names and behavior in code before citing them.

### 5. Plan an approval-aware, testable delivery

Preferred order for remaining work; update from actual repo and user priorities:

1. Integrate approved, reviewed security/assessment work through individually authorized PR merges.
2. Implement only the approved institutional-email + roster-match foundation; leave precise domain/registrar/recovery permissions in proposed state until decided.
3. Complete D13/D14/D15 role/identity authority with named school approvers and least-privilege, audited contracts.
4. Rehearse outstanding Laravel migrations, backup/restore and synthetic end-to-end accounts in **isolated PostgreSQL**; migrate `markado.public` only with separate explicit authorization.
5. Complete authenticated Student/Professor/Admin desktop/mobile and light/dark QA, keyboard/screen-reader accessibility checks and security threat review.
6. Validate HTTPS/session cookies, trusted proxy, scheduler leader, monitoring, backups and recovery; enable routes/workers in controlled staging only after separate approval. Deploy only when independently authorized.

A request scoped to one step does **not** authorize skipping ahead to the next.

### 6. Verification gates and evidence

- Laravel HTTP/policy tests: status transitions, session revocation, cross-role and cross-resource denial, quiz publication, eligible Start, safe result projection.
- PostgreSQL-specific tests: migrations/triggers, uniqueness, immutable revisions, concurrent Starts, version races, save/expiry and submit/expiry races, stable terminal receipts, scoring integrity and release idempotency.
- Frontend: ESLint, Vitest, root Vite build and `server/` Inertia build; verify route-backed real data rather than prototype mock state.
- Security: dependency audits, available secret scans, CSRF/throttling/authorization/privacy checks, email-token controls **when approved**.
- Visual/accessibility: actually rendered desktop/mobile, light/dark, keyboard, focus, contrast and assistive-technology evidence; report `NOT VERIFIED` if browser access is unsafe/unavailable. Never bypass browser safety checks.
- Operations: isolated migration/apply/repeat/backup/restore; explicit schema identity/search_path guard; no accidental `markado.public` writes, no unsanctioned scheduler or deployment.
- GitHub: verify **exact candidate tree** and fresh PR checks; for an approved merge, use merge commits and check post-merge main CI before advancing. Preserve feature branches; never force-push, silently rebase or bypass protections.

Report test counts, assertions, failure/skip causes, current commit/tree IDs and run URLs **only when observed**. A full SQLite PASS cannot replace PostgreSQL evidence, and an audit timeout is not a PASS.

### 7. Document without overstating

For a material approved decision, update a dated ADR and the decision/traceability records if specifically tasked. Record **approved scope, exclusions, actor, evidence, tests, rollback and operational gates**. Do not fabricate registrar, academic-committee or privacy-officer signatures.

Keep `MARKADO_Context_Pack/templates/PROGRESS_TRACKER.md` aligned with proved results. Old docs can describe earlier phases; do not rewrite historical claims as if they were never true. Do not edit unrelated `AGENTS.md`, production credentials, or protected database state during a review-only task.

## Evidence levels

Use explicit labels when useful:

- **APPROVED / OPEN / PROPOSED:** product-policy authority, not implementation.
- **IMPLEMENTED / DEFAULT-GATED:** code exists but normal user access is off.
- **UNIT PASS / SQLITE PASS / POSTGRESQL PASS:** specify environment and actual count; do not conflate them.
- **EXACT-CANDIDATE CI PASS / POST-MERGE CI PASS:** attach matching SHA/run evidence.
- **RENDERED QA PASS / QA BLOCKED / NOT TESTED:** disclose actual browser/device/accessibility coverage.
- **STAGING ACTIVE / PRODUCTION ACTIVE:** only after explicit authorization and observed activation.

A green PR, complete prototype, or user-approved decision is not, by itself, a `PRODUCTION ACTIVE` result.

## Stop / escalation conditions

Stop the **affected action** and request specific approval rather than guessing if it would:

- Change Laravel/PostgreSQL/React-Inertia, invent an email domain/registrar/SIS integration, or add a paid external provider.
- Enable public registration, password recovery, identity verification, privileged Admin actions, reset/regrade, AI publication, unapproved exports or score visibility.
- Activate quiz Start/Save/Submit or a scheduler for real school accounts without approved identity/operations gates.
- Migrate/seed/drop/reset `markado.public`, use real student records in synthetic tests, expose keys/PII, or delete protected user files.
- Merge an unauthorized PR, force-push, delete feature branches, bypass CI, deploy, or operate on a workspace other than the authorized target.
- Evade browser/command safety guards, invent visual QA outcomes, or claim test/CI PASS without evidence.
- Change approved D07 hard-close semantics, modify a published revision/snapshot, regenerate deadlines, or widen student result visibility.

Continue independent safe work where possible; describe precisely what is blocked and what evidence/decision resolves it.

## Required final output

Return **(1)** verified current state, **(2)** decision/recommendation and approval status, **(3)** affected services/routes/schema/UI and critical boundaries, **(4)** smallest implementation or review sequence, **(5)** acceptance tests and actual evidence, and **(6)** exact changes made versus only proposed, with blockers and next authorization needed.

Prefer a focused response over a broad roadmap unless requested. Speak in clear practical English or Taglish as appropriate, and never confuse prototype completeness, merged code, or default-gated functionality with a live institutional assessment deployment.
