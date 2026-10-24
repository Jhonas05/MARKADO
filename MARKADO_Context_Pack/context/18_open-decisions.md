# Decision Register - Confirmed Architecture and Remaining Open Choices

## How to interpret this register
The source PDF left deployment and all implementation technologies unspecified. On **22 October 2026**, the user confirmed an **online internet-hosted web app**, **Laravel (PHP)**, **PostgreSQL** and **React (Inertia.js)**. These are documented product architecture decisions, not implementation claims. Every other OPEN item requires product/technical/academic review, especially before implementing grading or time policies.

## Confirmed decisions [DECIDED]
- **D01:** Internet-hosted online web application. Students/instructors/admins access a central server over the internet. No LAN-only or fully offline per-device examination workflow is in current scope.
- **D25:** Laravel (PHP) is the backend/application framework; exact Laravel/PHP version remains D29 [OPEN].
- **D26:** PostgreSQL is the relational database engine; service hosting, backups and placement remain D28 [OPEN].
- **D27:** React with Inertia.js is the frontend integration; versions, design system and optional tooling remain [OPEN].
- **D24 [NOT_APPLICABLE]:** Local isolated-device data synchronization is not required for this online deployment. Temporary internet outages still need an explicit reconnection policy under D09.
- **ADR-004 (23 October 2026):** D16 immutable published revisions and D22 school-managed eligibility are approved for V1. Unpublish blocks new starts without deciding running-attempt behavior. Eligibility requires a verified active school record plus an active direct or section assignment and denies by default. D13-D15 verification and mutation authorities remain unresolved.
- **ADR-005 (23 October 2026):** D07 is decided as a hard global close. Eligible students may start strictly before close even when the full session duration will not fit; no start is accepted at or after close; every active attempt is automatically submitted at close using the latest server-acknowledged answers.
- **ADR-006 (23 October 2026):** D04-D06 and D08-D10 are decided for V1. Dual timers may run together and the earliest persisted server deadline wins; question expiry locks and advances without backward navigation; whole-session expiry finalizes atomically; one attempt resumes with stable order, answers, and deadlines; saves require server acknowledgement and receive no automatic outage grace; opening is inclusive, closing is exclusive, storage is UTC, and display is Asia/Manila. ADR-007 subsequently resolves the D05 final-question edge. Exceptional staff reset/accommodation authority remains unresolved.
- **ADR-007 (documented 24 October 2026; exact approval date not recorded):** D05 final-question expiry finalizes at its persisted question deadline; D12 gives each immutable MCQ/True-False snapshot one integer point for a correct stable choice and zero otherwise; D11 permits only the owning Professor to manually release score/total. No answer-key feedback, percentage, rank/tie policy, bulk release, regrade, or override is inferred. Approval evidence is the product owner's `g pre` reply in the project conversation.
- **ADR-003 (22 October 2026):** The product owner approved a single-school V1; single-answer MCQ and True/False; one resumable attempt; Asia/Manila display timezone; professor-controlled manual result release; student number and email as future registration requirements; and limited permission-based administrator access. Only those exact boundaries are decided. See `../decisions/ADR-003-phase-5b-approved-v1-boundaries.md` for the remaining sub-policies.

## Decision register
| ID | Decision / question | Status | Owner or reviewer |
|---|---|---|---|
| D01 | Online internet-hosted web application; not LAN-only or fully offline per-device | **DECIDED** | Product (user) |
| D02 | Single school vs multiple institutions/sections | **DECIDED** - single-school V1; D22 defines section eligibility | Product (user) |
| D03 | V1 question formats | **DECIDED** - single-answer MCQ and True/False | Product (user) |
| D04 | Per-question and whole-quiz timer interaction | **DECIDED** - both may run; earliest persisted question/session/global deadline wins | Product (user), 23 Oct 2026 |
| D05 | Behavior on per-question expiry | **DECIDED** - lock, preserve acknowledged answer, auto-advance, no back navigation; final question finalizes at its persisted deadline | Product (user); ADR-006 + ADR-007; exact later approval date not recorded |
| D06 | Quiz-time expiry behavior | **DECIDED** - atomically finalize once using acknowledged answers and return a stable receipt | Product (user), 23 Oct 2026 |
| D07 | In-progress attempts when quiz closes | **DECIDED** - hard close with automatic submission; starts allowed strictly before close | Product (user), 23 Oct 2026 |
| D08 | Allowed attempts and resume rules | **DECIDED WITH EXCEPTIONS OPEN** - one attempt resumes across refresh/sign-in/reconnect/tab/device with stable state; staff reset/accommodation authority remains unresolved | Product (user), 23 Oct 2026 |
| D09 | Late saves, outage and reconnection policy | **DECIDED WITH INSTITUTIONAL REMEDIES OPEN** - timers continue, no automatic grace, server acknowledgement only, versioned idempotent retries, authoritative reconnect | Product (user), 23 Oct 2026 |
| D10 | Timezone standard and open/close semantics | **DECIDED** - inclusive opening, exclusive closing, UTC persistence, Asia/Manila display, server clock authoritative | Product (user), 23 Oct 2026 |
| D11 | Result release timing and answer feedback | **DECIDED WITH EXCLUSIONS** - owning Professor manually releases score/total only; item feedback, answer keys, leaderboard, bulk/revoke remain excluded | Product (user); ADR-007; exact approval date not recorded |
| D12 | Ranking/scoring/tie and repeat-attempt aggregation rules | **DECIDED WITH EXCLUSIONS** - MCQ/True-False score 1 correct and 0 incorrect/unanswered; weighting, percentages, ranks, ties, repeats and regrading remain excluded/open | Product (user); ADR-007; exact approval date not recorded |
| D13 | Student number issuance and verification | **PARTIALLY DECIDED** - required field; issuance/verification remain open | Product (user) |
| D14 | Email verification and duplicate recovery | **PARTIALLY DECIDED** - required field; verification/recovery remain open | Product (user) |
| D15 | Instructor/administrator privileges | **PARTIALLY DECIDED** - limited permission-based administration; exact grants remain open | Product (user) |
| D16 | Live quiz edits, revision handling and unpublish | **DECIDED** - immutable published revisions; separate draft edits; explicit re-publish; unpublish blocks new starts without deciding running attempts | Product (user), 23 Oct 2026 |
| D17 | Uploads: file types, OCR and size limits | **OPEN** | To assign |
| D18 | AI provider and upload-processing permissions | **OPEN** | To assign |
| D19 | Retention/export expiry and backup periods | **OPEN** | To assign |
| D20 | Learning material ownership and copyright | **OPEN** | To assign |
| D21 | Supported devices/browsers/accessibility level | **OPEN** | To assign |
| D22 | Student class/section quiz eligibility | **DECIDED** - verified school record plus active direct or assigned-section membership; deny by default | Product (user), 23 Oct 2026 |
| D23 | Manual grading, regrading and appeal permissions | **OPEN** | To assign |
| D24 | Per-device offline synchronization (out of current scope) | **NOT_APPLICABLE** | Product (scope) |
| D25 | Laravel PHP application/backend framework | **DECIDED** | Product (user) |
| D26 | PostgreSQL database engine (host/location tracked in D28) | **DECIDED** | Product (user) |
| D27 | React frontend integrated via Inertia.js | **DECIDED** | Product (user) |
| D28 | Production hosting, queues, storage and recovery goals | **OPEN** | Technical + operations |
| D29 | Laravel version/PHP runtime and dependency support | **OPEN** | Technical lead |

The decision-ready D13/D14/D15/D23 recommendations in
`../../docs/v8-account-security-activation-readiness.md` are **PROPOSED only**.
They do not change any status in this register or authorize a mutation route.

## Architecture-dependent blockers [OPEN]
- **Platform:** PostgreSQL (D26) and React/Inertia.js (D27) are **DECIDED**. D28 hosting/managed PostgreSQL/queues/storage/backups and D29 Laravel/PHP plus React/Inertia dependency versions remain OPEN. Do not silently swap the database/frontend or infer Railway, AWS, Supabase, Tailwind, TypeScript or any provider/package.
- **Academic policy:** ADR-006 and ADR-007 resolve the ordinary V1 rules for D04-D12 only to their stated boundaries. D08/D09 exceptional staff or institution-wide remedies, D11 bulk/revoke/item-feedback behavior, D12 percentages/ranking/ties/repeats, and D23 regrade/override authority remain unresolved. D07 is fixed by ADR-005 and must not be weakened by implementation defaults.
- **Identity/privacy:** D13-D15 and D19-D21 still affect verification authority, privileged mutations, retention, and accessibility. D22 eligibility evaluation is decided.
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

## Decided deadline and attempt policies - D04-D10
At a 10:00 close, a student may start at 09:50 even with a 20-minute configured session, but the effective deadline is 10:00. The server automatically submits at close. A start at exactly 10:00 is denied. When both timer types are enabled, the earliest persisted question, session, or global deadline controls. Question expiry locks and advances; session/global expiry atomically finalizes. Resume and reconnect never regenerate ordering or deadlines, and only server-acknowledged answers count. See ADR-005 and ADR-006.

## Meeting agenda [REC]
**Do not re-open D01/D04-D12/D25-D27 just by guessing alternatives.** First choose D28/D29 for production operations. Next settle exceptional D08/D09 remedies, the explicit D11/D12 exclusions, D13-D15/D23 identity and privileged actions, D17/D18/D20 uploaded learning materials/AI, D19 retention, and D21 HCI support. D16 and D22 are recorded in ADR-004; D07 is recorded in ADR-005; D04-D06 and D08-D10 are recorded in ADR-006; the D05 final edge and minimal D11/D12 rules are recorded in ADR-007. Record named approver/date for each new decision.
