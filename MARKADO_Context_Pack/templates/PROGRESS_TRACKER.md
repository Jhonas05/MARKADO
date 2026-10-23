# MARKADO Progress Tracker

**Updated:** 23 October 2026. States distinguish interactive UI evidence from
production implementation. A prototype state does not claim Laravel,
PostgreSQL, authentication, authoritative timing, or policy completion.

| ID | Deliverable | State | Evidence | Blockers |
|---|---|---|---|---|
| F01 | Dual timers | POLICY APPROVED / ENGINE NOT ACTIVE | ADR-006 specifies server-authoritative precedence and expiry; prototype countdown remains display-only | Implement persistence/routes/concurrency; D05 final-question edge |
| F02 | Results + exports | UI PROTOTYPE | Local result preview and question breakdown in `src/App.jsx`; no export implementation | PDF/DOCX, D11-D12 |
| F03 | Access code/link | BACKEND FOUNDATION | Authenticated, rate-limited server lookup; immutable publication state; D22 eligibility gate; safe Inertia preflight | Implement and verify approved Start/resume engine; D13-D15 production identity/authority |
| F04 | AI draft generator | NOT STARTED | - | D17-D20 |
| F05 | Date/time availability | POLICY APPROVED / BACKEND FOUNDATION | ADR-005/006 define inclusive open, exclusive close, UTC persistence, Asia/Manila display, and expiry outcomes | Implement Start/session/question enforcement and operational workers |
| F06 | Student number + email | NOT STARTED | - | D02,D13-D14 |
| F07 | Randomization | NOT STARTED | - | D03 |
| F08 | Help / Guide | UI PROTOTYPE | Context-sensitive accessible panel and automated interaction test | Content approval and D21 |

Update based on actual repository and QA evidence only.

## Decision implementation ledger

| Decision | Product status | Implementation status | Verification status | Remaining boundary |
|---|---|---|---|---|
| D16 | APPROVED 23 Oct 2026 | IMPLEMENTED on Phase 5D feature branch: revisions, validation, ownership, publish/unpublish, audit | TESTED locally and by PR #10 GitHub Actions | Instructor-initiated unpublish behavior for active attempts remains separate from scheduled D07 close |
| D22 | APPROVED 23 Oct 2026 | IMPLEMENTED on Phase 5D feature branch: trusted records, direct/section assignments, deny-by-default preflight | TESTED locally and by PR #10 GitHub Actions | D13-D15 verification and privileged mutation authority |
| D07 | APPROVED 23 Oct 2026 | PREPARED on Phase 5E feature branch: hard-close deadline guard, persistence fields, dormant transactional finalizer | TESTED locally on PostgreSQL 17 and SQLite; PR #11 CI passed | Production Start/save/scheduler integration remains disabled pending implementation verification |
| D04-D06 | APPROVED 23 Oct 2026 | CONTRACT RECORDED in ADR-006; no production timer route activated | Documentation/traceability review pending PR CI | D05 final-question edge; persistence and concurrency implementation |
| D08-D10 | APPROVED 23 Oct 2026 WITH NAMED EXCEPTIONS | CONTRACT RECORDED in ADR-006; ordinary resume/connectivity/schedule behavior approved | Documentation/traceability review pending PR CI | Staff/institution-wide remedies; production identity/operations gates |
