# ADR-008: V1 institutional student email and trusted roster match

- **Status:** Decided — limited onboarding direction only
- **Documented:** 25 October 2026
- **Approved by:** Product owner in the MARKADO v8.2 project conversation
- **Scope:** D13/D14 institutional-email choice and required student identity match

## Context

ADR-003 requires a student number and email for future student registration.
ADR-004 requires a verified, active school-managed identity and an active direct
or section assignment before quiz eligibility. The remaining question was
whether V1 could accept a personal mailbox or must use an institution-issued
mailbox tied to the same authoritative roster identity.

## Decision

The product owner selected **Option A — school-issued institutional email only**.

1. V1 student onboarding requires a school-issued institutional email address;
   personal Gmail, Yahoo, and other personal mailboxes do not qualify.
2. The institutional mailbox must be verified.
3. The school-issued student number and institutional email must match the same
   authoritative school roster record.
4. Mailbox verification alone, a student number alone, or an allowed domain
   alone never activates an account or grants assessment eligibility.
5. Missing, mismatched, inactive, ambiguous, unverified, or unavailable trusted
   evidence denies access by default.
6. Laravel and PostgreSQL remain authoritative. Client state cannot select an
   allowed domain, establish a roster match, activate an account, or grant a
   role.
7. ADR-004's active direct/section assignment requirement remains mandatory
   after identity checks pass.

## Rejected direction

V1 will not accept personal-email onboarding. A personal mailbox cannot be
treated as equivalent to an institution-issued identity merely because its
owner supplies a student number.

## Engineering consequences

- Institutional domains come only from a trusted server-side exact-match
  allowlist. An empty allowlist denies by default. No real school domain is
  inferred or committed.
- Unicode domains are converted using a standards-based IDNA implementation
  before exact comparison. Subdomains and deceptive suffixes are not accepted
  unless separately configured.
- The email local part and student-number formatting are preserved; aliases,
  leading zeros, and case-sensitive local-part rules are not rewritten without
  a later school-specific decision.
- A trusted roster adapter must validate the account, official student number,
  institutional email, mailbox verification, roster status, and account status
  transactionally.
- Trusted roster email and student-number uniqueness are database-enforced.
  Duplicate claims cannot silently merge accounts or reassign attempts.
- Email changes invalidate mailbox verification and therefore the identity
  match until the approved process re-verifies it.
- Security events record only the account identifier, safe outcome code, and
  timestamp; they do not store email, student number, tokens, or answer data.

## Not decided

This decision does **not** approve:

- public self-service registration or invitation-only issuance;
- an actual institutional domain, SIS/registrar provider, roster file format,
  import authority, freshness interval, or correction procedure;
- verification-token lifetime, delivery service, resend limit, or recovery
  exception;
- account activation authority, administrator identity-verification powers,
  roster mutation, or privileged-account creation;
- duplicate/disputed-account resolution, account merging, lost-mailbox
  recovery, withdrawal, graduation, or retention rules;
- D15 permissions, D23 resets/accommodations/regrading/appeals, deployment, or
  enabling assessment and expiration processing.

Those sub-policies remain proposed or open and require separate approval.

## Security acceptance evidence

- Allow a synthetically configured institutional domain and deny an empty
  allowlist, personal domains, unlisted subdomains, and lookalike suffixes.
- Deny a verified mailbox without a trusted record, a matching number with the
  wrong email, a matching email with the wrong number, an inactive/unverified
  record, and a pending/disabled account.
- Enforce unique student number, roster email, and account linkage in
  PostgreSQL, including concurrent duplicate claims.
- Deny cross-student identity access and fail closed when the trusted source is
  unavailable.
- Keep institutional email out of globally shared Inertia props and protected
  identifiers out of identity audit events.
- Preserve ADR-004 assignment checks, PR #17 session revocation, disabled
  registration/recovery mutations, and default-off assessment switches.

## Revisit criteria

Revisit if the institution approves a provider, additional institutional
domains, mailbox aliases, multiple schools, a canonical student-number format,
or a governed registration/recovery/identity-correction workflow.

## Related decisions

- ADR-003: single-school V1 and student number/email requirement
- ADR-004: school-managed eligibility and direct/section assignment
- ADR-005 through ADR-007: assessment, deadline, scoring, and result release
