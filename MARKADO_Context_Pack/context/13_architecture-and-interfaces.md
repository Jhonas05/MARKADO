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
