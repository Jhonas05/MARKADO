# ADR-011 — V1 institutional email verification challenge

- **Status:** APPROVED for the exact D14 boundaries below; operations remain gated
- **Decision recorded:** 25 October 2026 (Asia/Manila)
- **Decision authority:** MARKADO product owner
- **Scope:** Institutional mailbox challenge mechanism supporting ADR-008 through ADR-010

## Decision

1. MARKADO uses a cryptographically random, high-entropy, single-use challenge to prove control of the Student account's institutional mailbox.
2. PostgreSQL stores only a keyed cryptographic hash of the secret. Plaintext secrets must not be persisted, logged, audited, placed in exceptions, or sent through Inertia props.
3. A challenge is valid for exactly 30 minutes from its persisted `issued_at`: it is accepted only while authoritative `server_now < expires_at`. The exact boundary and every later instant are invalid.
4. A new issue/resend is unavailable until exactly 60 seconds after the prior issue. At the 60-second boundary it may proceed.
5. A rolling-hour per-account/mailbox issue limit is required. The exact numeric maximum is **OPEN**. Five per hour is only a **PROPOSED** configuration candidate and has no production default.
6. Public request and resend responses are generic and do not reveal account, mailbox, student-number, roster, delivery, cooldown, or limit state.
7. Challenges bind to one Student account and a privacy-preserving fingerprint of its currently allowed institutional address. Email change clears prior proof and revokes outstanding challenges.
8. Successful consumption establishes mailbox-possession evidence only. It never activates an account, modifies a roster record, assigns a role, or grants quiz eligibility.
9. Consumption and issuance are serialized by Laravel/PostgreSQL transactions and row locks. Replay and competing consumption cannot create multiple successful states.
10. Delivery must target only the bound eligible institutional address. Delivery is default-unavailable until a school-approved provider, origin, monitoring policy, and operations authorization are configured. ADR-012 subsequently decided the hourly maximum.

Laravel/PostgreSQL time and state are authoritative. Browser time, refresh, delayed cleanup, or a client-supplied flag cannot extend or establish verification.

## Security invariants

- Token material is generated with a cryptographically secure random source and leaves the service only through the internal delivery contract.
- A challenge must be confirmed delivered before it can be consumed; failed/unavailable delivery revokes it.
- Consumed, revoked, expired, invalid, foreign-account, wrong-address, disabled-account, and non-Student challenges fail closed without account activation.
- Safe audit records contain only account identifier, outcome, and time—not email, student number, token, or roster contents.
- Network-aware anti-abuse is a separate reusable gate. IP address alone is not a sufficient school-network policy.

## Subsequent approved decision

ADR-012, recorded 25 October 2026, resolves item 5's previously open numeric
maximum: initial issuance and resends share a maximum of five requests per
rolling 60-minute window across the Student account and bound mailbox. A lower
positive configured value is allowed; an invalid value or any value above five
fails closed. Provider, origin, monitoring, recovery, and public activation
remain open/default-off.

## Activation boundary

The approved order remains:

`registration claim -> institutional-domain policy -> mailbox proof -> exact trusted-roster match -> current account/roster recheck -> approved activation-security gate -> atomic Student-only activation -> separate quiz eligibility`

The mailbox step may set verified-mailbox evidence. ADR-010 activation still requires every other approved check, keeps its security gate deny-by-default, and cannot activate a disabled, disputed, conflicting, or privileged account.

## OPEN operating decisions

- Network/account monitoring signals, alert thresholds, and incident handling. The numeric maximum is decided by ADR-012.
- Real institutional domains, SMTP/API provider, sending domain/DNS, approved application origin, and mail operational authorization.
- Pending-account/claim creation timing, registrar/SIS authority and freshness, duplicate/dispute procedure, lost-mailbox/password recovery, and identity correction.
- D15 privileged permissions, D19 challenge/audit retention and deletion schedule, and D23 exceptional assessment actions.

## Synthetic acceptance tests

- Random challenges differ; no plaintext token appears in challenge rows, identity audits, routes, or Inertia props.
- Valid consumption succeeds once; invalid, replayed, revoked, foreign, expired, disabled, and wrong-address attempts fail.
- Acceptance succeeds immediately before 30 minutes and fails exactly at 30 minutes and afterward.
- Resend fails before 60 seconds and may succeed exactly at 60 seconds.
- ADR-012's maximum-five rolling-hour policy is race-safe across the account and bound mailbox; lower positive configuration works and invalid/above-five configuration denies issuance.
- Concurrent consumption yields one success; concurrent issue attempts yield no duplicate live challenge.
- Email change clears `email_verified_at`, revokes outstanding challenges, and prevents an old token from verifying the new address.
- Mailbox proof alone leaves a pending Student pending; public registration/verification/recovery mutations remain absent.

## Activation and deployment exclusions

This ADR does **not** authorize real email, public registration/verification/resend/activation/recovery routes, `markado.public` migration, assessment activation, scheduler/worker activation, deployment, or any PR merge. Tests use synthetic identities and isolated databases only.

## Related decisions

- ADR-008: institutional mailbox plus same-record trusted roster identity
- ADR-009: Student self-registration direction
- ADR-010: bounded automatic Student-only activation
