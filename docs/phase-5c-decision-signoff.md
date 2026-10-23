# Phase 5C assessment-policy decision sign-off

**Status:** Decision support only. Recommendations below are not approvals and
must not activate assessment behavior.

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

The exact scope and exclusions are recorded in ADR-004.

## Proposed rules requiring explicit sign-off

| Decision | Recommended rule (PROPOSED) | Principal trade-off | Minimum acceptance evidence |
|---|---|---|---|
| D10 schedule | Store instants in UTC, display in Asia/Manila, make opening inclusive and closing exclusive. | Precise boundaries; instructors need clear wording. | Controllable-clock tests at one millisecond before, exactly at, and after both boundaries. |
| D04–D06 timers | Persist all approved deadlines at Start. D07 already requires the global close to cap them. Per-question and general session-expiry behavior remain proposed. | Deterministic integrity, but the unresolved expiry choices affect fairness and navigation. | Browser-clock tampering, reconnect, race, delayed request, and dual-deadline tests. |
| D08 resume/reset | Resume the same attempt across refresh, sign-in, and devices. Permit no student reset; any staff reset is separately permissioned, reasoned, and audited. | Protects one-attempt integrity; exceptional recovery needs staff support. | Concurrent Start creates one attempt; resume returns identical snapshot/order/deadlines. |
| D09 connectivity | A save succeeds only after a server acknowledgement containing persisted version and timestamp. Do not silently add a grace period. | Truthful state, but offline work is not guaranteed until acknowledged. | Idempotent retry, out-of-order save, lost acknowledgement, and deadline-race tests. |
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

Unresolved decisions after ADR-005: **D04–D06, D09, D12, D23**;
partially resolved: **D08, D10, D11, D13–D15**. D28 and D29 also remain open
for export/accessibility details and do not authorize assessment-engine behavior.

## Sources of truth

- `MARKADO_Context_Pack/decisions/ADR-003-phase-5b-approved-v1-boundaries.md`
- `MARKADO_Context_Pack/decisions/ADR-004-v1-publication-and-eligibility.md`
- `MARKADO_Context_Pack/decisions/ADR-005-v1-hard-close-auto-submission.md`
- `MARKADO_Context_Pack/context/18_open-decisions.md`
- `docs/phase-5c-assessment-engine-readiness.md`

If this document conflicts with an approved ADR, the approved ADR controls.
