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
- **ADR-008 (25 October 2026):** D13/D14 now require school-issued institutional email only, verified mailbox possession, and a matching authoritative roster student number and email. Missing or mismatched evidence denies by default. Public registration, token/recovery mechanics, roster authority, activation authority, D15, and D23 remain unresolved.
- **ADR-009 (25 October 2026):** Student self-registration is the approved normal V1 Student account-issuance direction. Registration still requires independent mailbox proof and an exact same-record active trusted-roster match; it cannot create or edit roster authority.
- **ADR-010 (25 October 2026):** A normal pending Student activates automatically only after every approved identity, account-state, security, and anti-abuse check succeeds. The transition is atomic, idempotent, Student-only, and separate from quiz eligibility. Public registration, delivery/recovery mechanisms, operational security parameters, and activation remain default-gated.
- **ADR-011 (25 October 2026):** D14 mailbox verification uses a random single-use hash-at-rest challenge, exact 30-minute validity, exact 60-second resend cooldown, required rolling-hour limiting, and generic public responses. It left the hourly number open; ADR-012 supersedes only that numeric gap. Real delivery provider/origin, monitoring, recovery, and activation operations remain open/default-off.
- **ADR-012 (25 October 2026):** D14 now caps initial verification issuance plus resends at five requests per Student account/bound mailbox in a rolling 60-minute window. Lower positive configuration is allowed; invalid or above-five configuration fails closed. This does not approve live delivery, network monitoring, recovery, or public activation.

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
| D13 | Student number issuance and verification | **PARTIALLY DECIDED** - official number must match the same authoritative roster identity as the institutional email; Student self-registration is the normal V1 issuance direction; issuer, source/import, freshness, correction authority, and canonicalization remain open | Product (user), 25 Oct 2026; ADR-008/009 |
| D14 | Email verification and duplicate recovery | **PARTIALLY DECIDED** - ADR-011/012 fix random single-use hash-only tokens, exact 30-minute expiry, 60-second cooldown, a maximum of five initial/resend issues per account/bound mailbox per rolling 60 minutes, generic responses, and mailbox-proof-only semantics; provider/origin, monitoring, recovery, duplicate/dispute operations, and public activation remain open/default-off | Product (user), 25 Oct 2026; ADR-008/009/010/011/012 |
| D15 | Instructor/administrator privileges | **PARTIALLY DECIDED** - limited permission-based administration; automatic Student activation grants no staff power and exact staff grants remain open | Product (user) |
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

ADR-008 through ADR-012 decide only their exact D13/D14 boundaries. The remaining
recommendations in `../../docs/v8-account-security-activation-readiness.md`,
`../../docs/v8-2-identity-permission-signoff.md`, and
`../../docs/v8-5-account-security-signoff.md` are **PROPOSED only** unless an ADR
marks the exact rule approved. No ADR here authorizes public registration,
recovery, roster/staff privilege mutations, assessment activation, or deployment.

## Architecture-dependent blockers [OPEN]
- **Platform:** PostgreSQL (D26) and React/Inertia.js (D27) are **DECIDED**. D28 hosting/managed PostgreSQL/queues/storage/backups and D29 Laravel/PHP plus React/Inertia dependency versions remain OPEN. Do not silently swap the database/frontend or infer Railway, AWS, Supabase, Tailwind, TypeScript or any provider/package.
- **Academic policy:** ADR-006 and ADR-007 resolve the ordinary V1 rules for D04-D12 only to their stated boundaries. D08/D09 exceptional staff or institution-wide remedies, D11 bulk/revoke/item-feedback behavior, D12 percentages/ranking/ties/repeats, and D23 regrade/override authority remain unresolved. D07 is fixed by ADR-005 and must not be weakened by implementation defaults.
- **Identity/privacy:** ADR-008 through ADR-012 fix institutional-email, same-record roster match, Student self-registration, bounded automatic Student-only activation, the narrow mailbox-challenge semantics, and a maximum of five issue/resend requests per account/bound mailbox per rolling 60 minutes. Roster authority/source/freshness, live delivery/provider/origin, network monitoring, recovery/disputes, D15 staff grants, and D19-D21 retention/accessibility rules remain open. Public mutations remain disabled. D22 eligibility evaluation is decided.
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
