# Phase 5C assessment-policy decision sign-off

**Status:** ADR-003 through ADR-007 approvals are recorded below. Remaining
recommendations are not approvals and must not activate blocked behavior.

**Prepared:** 23 October 2026

## Confirmed product-owner approvals

These are the only assessment boundaries confirmed by ADR-003:

1. MARKADO V1 is single-school; section membership and eligibility rules remain
   open.
2. Question formats are single-answer multiple choice and True/False; points,
   partial credit, rounding, ties, and ranking remain open.
3. A student has one resumable attempt per quiz; creation, expiry, retry/reset,
   outage, and multi-device semantics remain open.
4. User-facing dates and times display in `Asia/Manila`; schedule boundaries and
   storage/conversion details remain open.
5. Professors manually control result release; score calculation and released
   feedback contents remain open.
6. Future student registration requires student number and email; verification,
   uniqueness, recovery, and self-service rules remain open.
7. Administrators have limited, permission-based access; the permission matrix
   and audit requirements remain open.

No prototype copy, schema default, recommendation, or interface in this branch
expands those approvals.

On 23 October 2026 the product owner also approved:

8. **D16:** immutable published quiz revisions, separate editable drafts,
   explicit validated re-publication, preserved attempt mappings, and
   unpublish blocking new starts without deciding running-attempt behavior.
9. **D22:** verified school-managed student records with active direct or
   assigned-section eligibility; codes do not authorize; missing, inactive,
   unverified, unauthorized, or unavailable records deny by default.
10. **D07:** eligible students may start strictly before close even when the
    full session duration will not fit; no start is accepted at or after close;
    active attempts are automatically submitted at close using acknowledged
    answers. The global close caps attempt and question deadlines.
11. **D04:** whole-quiz and per-question timers may run together; the earliest
    applicable persisted server deadline wins.
12. **D05:** question expiry locks the question, preserves the last acknowledged
    answer, auto-advances once, and disables backward navigation. ADR-007 later
    decides that final-question expiry finalizes at its persisted deadline.
13. **D06:** whole-session expiry atomically finalizes the attempt using only
    acknowledged answers and returns a stable idempotent receipt.
14. **D08:** one attempt resumes across refresh, browser restart, sign-in,
    reconnect, tab, or device with stable content, order, answers, and deadlines;
    student reset is denied and staff exceptions remain unresolved.
15. **D09:** timers continue during connectivity loss; no automatic grace is
    granted; only server-acknowledged answers count; idempotent/version-aware
    retries restore authoritative state. Institution-wide remedies remain open.
16. **D10:** opening is inclusive, closing is exclusive, instants are stored in
    UTC, school-facing times display in Asia/Manila, and browser time is never
    authoritative.

ADR-007, documented 24 October 2026, records the product owner's `g pre`
approval of the minimal D05/D11/D12 recommendations. The exact approval date is
not recorded and is not inferred. It approves final-question finalization,
objective 1/0 MCQ/True-False scoring, and owning-Professor manual release of
score/total only. The exact scope and exclusions are recorded in ADR-004
through ADR-007.

## Proposed rules requiring explicit sign-off

| Decision | Recommended rule (PROPOSED) | Principal trade-off | Minimum acceptance evidence |
|---|---|---|---|
| D08/D09 exceptional remedy | Define who may grant a reset, accommodation, or institution-wide outage remedy, with limits and audit evidence. | Supports exceptional fairness without weakening ordinary one-attempt/no-grace integrity. | Least-privilege authorization, reason/audit, deadline recalculation, and cross-student denial tests. |
| D11/D12 exclusions | Decide any future ranking/tie presentation, percentages, exports, bulk/revoked release, weighting, item feedback, override, or regrade behavior separately. | Adds reporting power but expands privacy and academic-integrity risk. | Separate approval plus authorization, privacy, deterministic scoring, and audit tests. |
| D13–D14 identity | School-issued student number plus verified institutional email must map to one account; duplicate resolution is staff-managed and audited. | Reduces impersonation, but requires identity operations and recovery support. | Duplicate, mismatched, unverified, recovered, and deactivated-account tests. |
| D15/D23 privileged actions | Use named permissions per resource and action; require reason, actor, before/after state, and timestamp for overrides, regrades, releases, and access changes. | Strong accountability with added administrative friction. | Cross-resource denial, least-privilege matrix, audit completeness, and tamper-evidence review. |

ADR-007 removes the D05 final-question recommendation and the minimal
D11/D12 1/0 scoring plus score/total-only manual release rule from this proposed
table. Only the exclusions around ranking, ties, percentages, exports, bulk or
revoked release, item feedback, overrides, and regrading still require sign-off.

## Sign-off record

For every approved row, record all fields below in a dated ADR before enabling
the related capability:

- Decision IDs and exact selected behavior
- Product owner and academic-policy approver
- Approval date and effective version
- Student- and professor-facing wording
- Boundary examples and exception handling
- Required automated and manual acceptance tests
- Rollback, incident, and audit expectations

Unresolved after ADR-007: exceptional **D08/D09** staff/institution-wide
remedies; D11/D12 ranking, ties, percentages, exports, weighting, bulk/revoked
release, and item-feedback exclusions; **D13–D15** identity/privileged
authority; and **D23** override/regrade authority. D28 and D29 also remain open
for production operations/runtime choices.

## Sources of truth

- `MARKADO_Context_Pack/decisions/ADR-003-phase-5b-approved-v1-boundaries.md`
- `MARKADO_Context_Pack/decisions/ADR-004-v1-publication-and-eligibility.md`
- `MARKADO_Context_Pack/decisions/ADR-005-v1-hard-close-auto-submission.md`
- `MARKADO_Context_Pack/decisions/ADR-006-v1-dual-timers-and-attempt-policy.md`
- `MARKADO_Context_Pack/decisions/ADR-007-v1-final-question-scoring-and-result-release.md`
- `MARKADO_Context_Pack/context/18_open-decisions.md`
- `docs/phase-5c-assessment-engine-readiness.md`

If this document conflicts with an approved ADR, the approved ADR controls.
