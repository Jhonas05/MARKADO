# ADR-009 — V1 student self-registration with authoritative roster and institutional mailbox verification

- **Decision status:** APPROVED DIRECTION ONLY (product-owner choice B); supporting implementation mechanisms remain OPEN.
- **Decision recorded:** 25 October 2026 (Asia/Manila).
- **Decision authority:** MARKADO product owner, in the project conversation. No registrar, academic-policy, privacy-officer, or operational approval is implied.
- **Repository integration status:** Prepared outside Git; NOT COMMITTED or merged.
- **Scope:** D13/D14 student account *issuance method*, building upon ADR-003, ADR-004, and ADR-008.
- **Repository checkpoint inspected:** `main` at `5f01263723ade995e4aa1d36db025a612d0d582f`.

## Context

ADR-003 requires a student number and email for future registration. ADR-004 requires verified active school-managed identity plus active direct/section quiz assignment. ADR-008 approves school-issued institutional email only and a trusted roster record linking that email to the official student number, but expressly left the self-registration versus institutional provisioning choice open.

The product owner has now selected **Option B: student self-registration only with exact official roster and verified institutional email matching**.

## Approved decision: exact narrow boundaries

1. The **normal V1 Student account-issuance path is student self-registration**: a student submits their own signup request. Staff-precreated Student accounts or invitation-only issuance are **not the chosen normal onboarding path**. Exceptional institutional support/recovery and non-Student account provisioning remain separate open policies.
2. Registration uses a **school-issued institutional email** and **official school-issued student number**, both required. Personal mailboxes are not acceptable.
3. The trusted school-controlled roster must authoritatively associate the **same student record**, student number, and institutional email. A student-supplied pair is not sufficient evidence of membership or ownership.
4. The applicant must **verify control of the institutional mailbox** through a subsequently approved secure method.
5. An allowed email domain alone, a student number alone, an email possession check alone, or a matching but unverified/stale/inactive school record does **not** authorize assessment participation or automatically establish an active account.
6. Missing, ambiguous, duplicated, inactive, unavailable, or mismatched roster evidence **fails closed**. Self-registration does not let a student create, edit, verify, or activate their own authoritative roster record.
7. Existing eligibility rules under ADR-004 remain mandatory: active authenticated Student account, verified active roster identity, and active quiz-specific direct or section assignment. Access code/link is never authorization.
8. Laravel enforces identity, access, and account-state decisions; PostgreSQL enforces integrity constraints. React/Inertia state is not authoritative.
9. Pending, disabled, and unverified states must not gain protected workspace or assessment access by virtue of signup.
10. This approval **does not authorize turning on live registration or recovery routes, activating examinations, migrating `markado.public`, deployment, or merging any PR**.

## Clarification: approved direction versus proposed workflow

A potential secure implementation is **PROPOSED, NOT APPROVED**: accept a rate-limited student signup request; perform private school-domain/roster checks without disclosing record existence; hold any created claim/account in `pending`; send a single-use verification challenge to the institutional mailbox; re-check trusted roster/current account status upon verification; and activate Student access **only through a separately approved D15 authority/mechanism**. Exact account creation timing, verification token method, activation authority, and success/failure messaging require later approval.

No teacher or Administrator self-registration privilege is inferred. No student may choose a role or active status in request data.

## Explicitly NOT decided

- The institution's real allowed email domain(s), registrar/SIS system, roster import format, authorized maintainer, freshness/disable interval, or school-specified student-number canonicalization.
- Whether a new `pending` account/claim is persisted before mailbox verification; safe handling of concurrent claims and partial signups.
- The verification service, token design and expiry, delivery channel/provider, resend limits, or recovery mechanism.
- Whether activation occurs automatically **after all independent controls pass** under an explicitly approved server-side rule, or requires staff approval per account; who may activate/deactivate and audit that event.
- Duplicate/disputed account ownership, mailbox changes, lost institutional mailbox, transfer/withdrawal/graduation/return, and record correction/merging rules.
- D15 named privileges, permission assignment/separation-of-duties, D19 retention/privacy, and all D23 exceptional assessment actions.

## Security acceptance criteria for a future implementation

- Reject personal/unconfigured/lookalike email domains and client-supplied domain allowlists; do not invent a school domain.
- Verify that student number and institutional email match **one verified active authoritative record**, not two different records; preserve leading zeros and local-part semantics until an approved normalization contract exists.
- Fail closed for missing, stale, inactive, disabled, duplicate, and unavailable trusted sources; prevent an unverified pending signup from starting or resuming attempts.
- Avoid exposing whether an email/student number exists in public responses, timing where practical, error details, or email issuance status.
- Keep no sensitive student number or email in global Inertia props or privacy-safe audit-event payloads; never log passwords or verification tokens.
- Assert database-level uniqueness, one account/roster association, concurrent duplicate-claim safety, and non-reassignment of historical attempts and grades.
- Verify role/status are server-assigned and resource-scoped, not selected by the browser.
- Recheck roster/account status at verification and protected access; enforce ADR-004 eligibility separately.
- Test verification token expiry/replay/resend/throttle and account recovery/session revocation **once those mechanisms are approved**.
- Keep `MARKADO_ASSESSMENT_ROUTES_ENABLED=false` and `MARKADO_ASSESSMENT_EXPIRATION_ENABLED=false` in non-test environments until separate activation approval.

## Documentation updates needed when repository changes are authorized

- Add this ADR as `MARKADO_Context_Pack/decisions/ADR-009-v1-student-self-registration-direction.md` **after confirming the number is still free**.
- Update `MARKADO_Context_Pack/context/18_open-decisions.md`: D13/D14 PARTIALLY DECIDED; student-initiated signup direction approved; technical issuance/verification/recovery/activation authority remains open.
- Update `MARKADO_Context_Pack/context/06_student-registration-and-access.md` and `docs/v8-2-identity-permission-signoff.md` to distinguish approved self-registration from prior proposed invitation/pre-provisioning recommendations.
- Reference ADR-008 without rewriting its historical statement that this was undecided when ADR-008 was adopted.
- Do **not** activate mutation routes or modify `markado.public` solely to record this ADR.

## Revisit

Revisit only if the school requires invitations/staff-provisioning, changes the canonical identity source, or formally alters institutional email, roster matching, or registration policy. Preserve the historical approval record rather than silently overwriting ADR-008.
