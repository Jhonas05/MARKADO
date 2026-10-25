# ADR-012 — V1 institutional email verification hourly limit

- **Status:** APPROVED for the exact D14 boundary below; operations remain gated
- **Decision recorded:** 25 October 2026 (Asia/Manila)
- **Decision authority:** MARKADO product owner
- **Scope:** Issue and resend ceiling for ADR-011 institutional mailbox challenges

## Decision

1. A Student account and its bound institutional mailbox share a maximum of **five verification-email issuance requests in a rolling 60-minute window**.
2. The initial issuance and every resend request count toward the same ceiling.
3. A lower positive deployment limit may be configured. No configuration may increase the limit above five; a missing-invalid effective policy fails closed.
4. The window is server-authoritative. An issue is inside the window while `issued_at > server_now - 60 minutes`; an issue exactly 60 minutes old has left the window.
5. Counting and issuance must be serialized so concurrent requests cannot create a sixth issuance. Per-account controls must not be bypassed by another pending account using the same bound mailbox.
6. An issuance row counts once it is created, including when downstream delivery fails or is delayed. This conservative rule prevents repeated provider failures from becoming an abuse bypass.
7. Public acknowledgements remain generic and must not disclose whether the account, mailbox, cooldown, hourly ceiling, roster identity, or delivery state exists.

ADR-011's 30-minute lifetime, 60-second resend cooldown, hash-only secret storage, mailbox binding, single use, exact-boundary behavior, and separation from activation and quiz eligibility remain unchanged.

## Implementation and security notes

- The default configuration is five; valid lower integer values are accepted. Values below one, above five, or of an invalid type make issuance unavailable.
- Laravel/PostgreSQL transactions and mailbox-scoped serialization enforce the account/mailbox ceiling. PostgreSQL concurrency tests are required for the fifth/sixth boundary.
- Delivery completion is checked using a fresh authoritative server timestamp. A challenge that reaches expiry before delivery completes is revoked and cannot be consumed.
- Failed delivery challenges are revoked but remain auditably countable within the rolling window. Raw token values, mailbox addresses, and network identifiers do not enter identity audit events.
- Rotating `APP_KEY` invalidates outstanding token hashes and changes mailbox fingerprints. Account-scoped rows still count, but a second account using the same mailbox would no longer match the pre-rotation fingerprint. The safe V1 operational rule is to disable issuance, drain the 60-minute rolling window, rotate, then re-enable only under separately approved operations; affected Students request a new challenge. Previous-key verification fallback is not approved by this ADR.

## Acceptance tests

- Requests one through five may issue when cooldown and all independent gates permit; the sixth request within the same rolling hour is denied.
- At exactly 60 minutes after the oldest issue, that oldest issue no longer counts.
- Simultaneous fifth and sixth requests produce at most five rows and one new live challenge across the bound account/mailbox scope.
- Failed delivery still consumes one hourly issuance; delayed delivery at or beyond expiry is revoked.
- Valid lower configuration works; invalid, non-integer, zero, negative, and above-five configuration fails closed.
- Token replay, expiry, wrong account, changed email, disabled account, abuse-gate denial, and public mutation absence remain covered by the ADR-011 security suite.

## Explicit exclusions

This decision does **not** approve a mail provider, institutional domains, sender/DNS, public registration/verification/resend routes, network-monitoring rules, recovery, duplicate/dispute operations, roster authority, production activation, protected-schema migration, deployment, or live assessment activation.

## Related decisions

- ADR-008: institutional mailbox plus same-record trusted roster identity
- ADR-009: Student self-registration direction
- ADR-010: bounded automatic Student-only activation
- ADR-011: institutional email verification challenge lifecycle
