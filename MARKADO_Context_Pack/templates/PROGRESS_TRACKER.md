# MARKADO Progress Tracker

**Updated:** 24 October 2026. States distinguish interactive UI evidence from
production implementation. A prototype state does not claim Laravel,
PostgreSQL, authentication, authoritative timing, or policy completion.

| ID | Deliverable | State | Evidence | Blockers |
|---|---|---|---|---|
| F01 | Dual timers | IMPLEMENTED / DEFAULT-DISABLED | ADR-006/007; persisted server deadlines, request-time reconciliation, bounded expiry command, PostgreSQL tests; React countdown is display-only | Operational scheduler activation; D13-D15 production authority |
| F02 | Results + exports | MINIMAL RESULTS IMPLEMENTED / EXPORTS PROTOTYPE | ADR-007 objective scoring, owner-only repeat-safe release, student score/total projection; no real export | Rank/tie and PDF/DOCX policy/implementation; D23 |
| F03 | Access code/link | GATED FLOW IMPLEMENTED | Authenticated, rate-limited preflight plus explicit Start/resume behind default-off server activation | D13-D15 production identity/authority and operations activation |
| F04 | AI draft generator | NOT STARTED | - | D17-D20 |
| F05 | Date/time availability | IMPLEMENTED / DEFAULT-DISABLED | UTC persistence, Asia/Manila authoring, immutable revision snapshot, Start/session/question enforcement, scheduled command tests | Operational scheduler and migration rehearsal |
| F06 | Student number + email | NOT STARTED | - | D02,D13-D14 |
| F07 | Randomization | IMPLEMENTED / DEFAULT-DISABLED | Stable per-attempt question/choice permutation IDs and snapshot-key scoring tests | Unsupported order-sensitive formats remain out of scope |
| F08 | Help / Guide | UI PROTOTYPE | Context-sensitive accessible panel and automated interaction test | Content approval and D21 |

Update based on actual repository and QA evidence only.

## Decision implementation ledger

| Decision | Product status | Implementation status | Verification status | Remaining boundary |
|---|---|---|---|---|
| D16 | APPROVED 23 Oct 2026 | IMPLEMENTED on Phase 5D feature branch: revisions, validation, ownership, publish/unpublish, audit | TESTED locally and by PR #10 GitHub Actions | Instructor-initiated unpublish behavior for active attempts remains separate from scheduled D07 close |
| D22 | APPROVED 23 Oct 2026 | IMPLEMENTED on Phase 5D feature branch: trusted records, direct/section assignments, deny-by-default preflight | TESTED locally and by PR #10 GitHub Actions | D13-D15 verification and privileged mutation authority |
| D07 | APPROVED 23 Oct 2026 | IMPLEMENTED behind default-off routes/scheduler flags | TESTED locally on PostgreSQL 17 and SQLite, including delayed and concurrent terminal requests | Operational activation and migration rehearsal |
| D04-D06 | APPROVED; D05 final edge recorded in ADR-007 | IMPLEMENTED behind default-off flags using one deadline reconciliation path | TESTED locally on PostgreSQL 17 and SQLite, including delayed multi-deadline reconciliation | Exceptional interventions remain outside ordinary flow |
| D08-D10 | APPROVED 23 Oct 2026 WITH NAMED EXCEPTIONS | IMPLEMENTED for ordinary resume/connectivity/schedule behavior | TESTED locally, including actual concurrent PostgreSQL Start and save/finalization processes | Staff/institution-wide remedies; production identity/operations gates |
| D11-D12 | APPROVED WITH EXCLUSIONS; exact approval date not recorded | IMPLEMENTED objective 1/0 scoring and owner-only score/total release behind default-off routes | TESTED locally on PostgreSQL 17 and SQLite; CI pending V7 PR | Ranking, ties, percentages, exports, bulk/revoke, overrides, regrade |
