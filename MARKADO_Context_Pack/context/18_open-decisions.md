# Decision Register - Confirmed Architecture and Remaining Open Choices

## How to interpret this register
The source PDF left deployment and all implementation technologies unspecified. On **22 October 2026**, the user confirmed an **online internet-hosted web app**, **Laravel (PHP)**, **PostgreSQL** and **React (Inertia.js)**. These are documented product architecture decisions, not implementation claims. Every other OPEN item requires product/technical/academic review, especially before implementing grading or time policies.

## Confirmed decisions [DECIDED]
- **D01:** Internet-hosted online web application. Students/instructors/admins access a central server over the internet. No LAN-only or fully offline per-device examination workflow is in current scope.
- **D25:** Laravel (PHP) is the backend/application framework; exact Laravel/PHP version remains D29 [OPEN].
- **D26:** PostgreSQL is the relational database engine; service hosting, backups and placement remain D28 [OPEN].
- **D27:** React with Inertia.js is the frontend integration; versions, design system and optional tooling remain [OPEN].
- **D24 [NOT_APPLICABLE]:** Local isolated-device data synchronization is not required for this online deployment. Temporary internet outages still need an explicit reconnection policy under D09.
- **ADR-003 (22 October 2026):** The product owner approved a single-school V1; single-answer MCQ and True/False; one resumable attempt; Asia/Manila display timezone; professor-controlled manual result release; student number and email as future registration requirements; and limited permission-based administrator access. Only those exact boundaries are decided. See `../decisions/ADR-003-phase-5b-approved-v1-boundaries.md` for the remaining sub-policies.

## Decision register
| ID | Decision / question | Status | Owner or reviewer |
|---|---|---|---|
| D01 | Online internet-hosted web application; not LAN-only or fully offline per-device | **DECIDED** | Product (user) |
| D02 | Single school vs multiple institutions/sections | **PARTIALLY DECIDED** - single school; section and eligibility rules remain open | Product (user) |
| D03 | V1 question formats | **DECIDED** - single-answer MCQ and True/False | Product (user) |
| D04 | Per-question and whole-quiz timer interaction | **OPEN** | To assign |
| D05 | Behavior on per-question expiry | **OPEN** | To assign |
| D06 | Quiz-time expiry behavior | **OPEN** | To assign |
| D07 | In-progress attempts when quiz closes | **OPEN** | To assign |
| D08 | Allowed attempts and resume rules | **PARTIALLY DECIDED** - one resumable attempt; retry/lifecycle details remain open | Product (user) |
| D09 | Late saves, outage and reconnection policy | **OPEN** | To assign |
| D10 | Timezone standard and open/close semantics | **PARTIALLY DECIDED** - Asia/Manila display; schedule semantics remain open | Product (user) |
| D11 | Result release timing and answer feedback | **PARTIALLY DECIDED** - professor-controlled manual release; feedback details remain open | Product (user) |
| D12 | Ranking/scoring/tie and repeat-attempt aggregation rules | **OPEN** | To assign |
| D13 | Student number issuance and verification | **PARTIALLY DECIDED** - required field; issuance/verification remain open | Product (user) |
| D14 | Email verification and duplicate recovery | **PARTIALLY DECIDED** - required field; verification/recovery remain open | Product (user) |
| D15 | Instructor/administrator privileges | **PARTIALLY DECIDED** - limited permission-based administration; exact grants remain open | Product (user) |
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
