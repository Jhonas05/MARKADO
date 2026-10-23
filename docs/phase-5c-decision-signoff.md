# Phase 5C assessment-policy decision sign-off

**Status:** ADR-003 through ADR-006 approvals are recorded below. Remaining
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
    answer, auto-advances once, and disables backward navigation. The final-
    question expiry transition remains unresolved and disabled.
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

The exact scope and exclusions are recorded in ADR-004, ADR-005, and ADR-006.

## Proposed rules requiring explicit sign-off

| Decision | Recommended rule (PROPOSED) | Principal trade-off | Minimum acceptance evidence |
|---|---|---|---|
| D05 final-question edge | Decide whether expiry on the final question immediately finalizes, waits only for an earlier session/global deadline, or presents another explicitly bounded transition. | Prevents invented extra time or an accidental early terminal state. | Exact-boundary test with no next question plus duplicate/race coverage. |
| D08/D09 exceptional remedy | Define who may grant a reset, accommodation, or institution-wide outage remedy, with limits and audit evidence. | Supports exceptional fairness without weakening ordinary one-attempt/no-grace integrity. | Least-privilege authorization, reason/audit, deadline recalculation, and cross-student denial tests. |
| D11–D12 scoring/release | For V1, award one point only for the exact correct choice, with no negative or partial credit. Release total score manually; withhold keys and item feedback unless separately approved. | Easy to explain and test, but limits question weighting and formative feedback. | Forged-score rejection, deterministic recalculation, unreleased-result denial, and repeat-safe manual release tests. |
| D13–D14 identity | School-issued student number plus verified institutional email must map to one account; duplicate resolution is staff-managed and audited. | Reduces impersonation, but requires identity operations and recovery support. | Duplicate, mismatched, unverified, recovered, and deactivated-account tests. |
| D15/D23 privileged actions | Use named permissions per resource and action; require reason, actor, before/after state, and timestamp for overrides, regrades, releases, and access changes. | Strong accountability with added administrative friction. | Cross-resource denial, least-privilege matrix, audit completeness, and tamper-evidence review. |

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

Unresolved after ADR-006: the **D05 final-question edge**, exceptional **D08/D09**
staff/institution-wide remedies, **D11** feedback details, **D12** scoring and
ranking, **D13–D15** identity/privileged authority, and **D23** override/regrade
authority. D28 and D29 also remain open for production operations/runtime choices.

## Sources of truth

- `MARKADO_Context_Pack/decisions/ADR-003-phase-5b-approved-v1-boundaries.md`
- `MARKADO_Context_Pack/decisions/ADR-004-v1-publication-and-eligibility.md`
- `MARKADO_Context_Pack/decisions/ADR-005-v1-hard-close-auto-submission.md`
- `MARKADO_Context_Pack/decisions/ADR-006-v1-dual-timers-and-attempt-policy.md`
- `MARKADO_Context_Pack/context/18_open-decisions.md`
- `docs/phase-5c-assessment-engine-readiness.md`

If this document conflicts with an approved ADR, the approved ADR controls.
