# ADR-010 — V1 bounded automatic Student activation

- **Decision status:** APPROVED DIRECTION ONLY (product-owner choice A); operational mechanisms and activation remain gated.
- **Decision recorded:** 25 October 2026 (Asia/Manila).
- **Decision authority:** MARKADO product owner, in the project conversation. No registrar, school IT, security, privacy-officer, or deployment approval is implied.
- **Scope:** D14 normal Student account activation after ADR-009 self-registration.

## Decision

A normal Student account becomes active automatically, without per-account staff approval, only after the server freshly confirms every independently approved identity and account-security requirement.

The bounded activation decision must revalidate:

1. Current server-controlled institutional-domain policy.
2. Independently verified institutional mailbox ownership.
3. Exact authoritative student number.
4. Same-record institutional email and student-number binding.
5. Current verified and active trusted roster status.
6. Approved account-security and anti-abuse gates.
7. Existing account state and ownership.
8. Absence of unresolved duplicate, disputed, or conflicting identity claims.

Missing, stale, ambiguous, inactive, disputed, or unavailable evidence fails closed. The transition is atomic, idempotent, server-authorized, and privacy-safely audited.

Automatic activation can assign only the `student` role. It never assigns Instructor, Academic Administrator, roster mutation, grade modification, or other privileged authority. A disabled, revoked, or disputed account cannot self-reactivate. Successful activation does not establish quiz eligibility; ADR-004 assignment checks and assessment feature gates remain independent.

## Implementation boundary

- Pre-activation claim verification is separate from active-account authorization.
- A trusted roster identity may exist before a MARKADO user and may be linked only during the guarded transition.
- The existing active-account identity evaluator remains strict and is not weakened for signup.
- Public registration mutations, verification delivery, recovery, and the operational anti-abuse gate remain disabled by trusted server configuration.
- The repository may contain dormant contracts, services, additive migrations, and synthetic tests without activating the public lifecycle.

## Not approved

- Real institutional domains, registrar/SIS integration, roster import, or staff roster mutations.
- Verification token format, storage, lifetime, delivery provider, resend/attempt limits, or recovery.
- Exact pending-claim persistence timing or duplicate/dispute resolution procedure.
- D15 staff permission grants or D23 resets, accommodations, regrading, and appeals.
- Migration of `markado.public`, deployment, public account creation, or live assessment activation.

## Acceptance criteria

- A pending Student activates only after every required gate passes.
- Instructor/Administrator roles cannot be selected or obtained through activation.
- Disabled, disputed, mismatched, unverified, inactive, or unavailable evidence denies without mutation.
- Repeated and concurrent activation requests converge on one account/roster binding and one activation event.
- No public route is enabled by this ADR; default configuration stays off.
- Audit events contain safe identifiers/outcomes only, never student numbers, emails, passwords, or verification secrets.

## Revisit

Revisit when the school approves the verification mechanism, anti-abuse controls, roster operations, recovery, and staging activation plan, or if the product owner changes the automatic Student-only activation direction.
