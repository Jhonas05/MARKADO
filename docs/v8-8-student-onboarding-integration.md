# MARKADO v8.8 — Student onboarding integration and verification security

**Prepared:** 25 October 2026 (Asia/Manila)

**Policy sources:** ADR-008 through ADR-012

**Operational state:** foundation only; public mutations and real delivery remain disabled

## Integrated service path

The verified onboarding path composes the existing Laravel services; it does not introduce a second registration engine or a public coordinator:

`pending Student -> allowed institutional address -> issue one-time mailbox challenge -> consume valid bound challenge -> exact active verified roster match -> deny-by-default activation security gate -> atomic Student-only link/activation -> independent quiz eligibility`

The synthetic integration harness proves that mailbox verification leaves the Student pending, automatic activation occurs only after the roster and test-only security gate both match, and activation alone does not grant quiz access. An active direct/section assignment is still required by the separate quiz eligibility gate.

## ADR-012 rate-limit contract

- Initial issuance and resends share a maximum of five requests per rolling 60-minute window across the Student account and its bound institutional mailbox.
- A lower positive integer may be configured. Invalid, missing-effective, zero, negative, non-integer, or above-five runtime policy makes issuance unavailable.
- PostgreSQL mailbox-scoped advisory locking plus transactional account locking prevents simultaneous requests from producing a sixth issuance or duplicate live challenge.
- Every created challenge counts, including one later revoked because delivery failed, delivery completed at/after expiry, or the bound address changed.
- The public acknowledgement remains identical for issuance, cooldown, limit, account, roster, and delivery outcomes.

## Delivery, origin, and key rotation

Real mail delivery remains unavailable through the production binding. Before staging, the school must approve the provider, exact institutional domains, sender identity/DNS, HTTPS application origin, monitoring, incident response, and secret handling. Verification URLs must be generated only from an approved trusted origin; request-controlled host data must not define the destination.

Challenge token hashes and mailbox fingerprints use the current application key. Rotating `APP_KEY` therefore invalidates outstanding challenges and changes the cross-account mailbox rate-limit fingerprint; account-scoped issuance rows still count. The safe operational sequence is to disable issuance, drain the 60-minute rolling window, rotate, then re-enable only under separately approved operations. Affected Students receive a generic failure and request a new challenge. Previous-key verification fallback is not approved, and no plaintext challenge material is preserved.

## Failure and privacy matrix

The Laravel/PostgreSQL suite traces:

- wrong student number or mailbox, inactive/disputed roster, identity already owned by another account, and disabled account;
- token replay, exact expiry, revoked/foreign/wrong-address token, changed address, failed delivery, and delivery delayed to expiry;
- first through fifth issuance, sixth denial, exact rolling-hour boundary, lower configuration, invalid/above-five configuration, and concurrent fifth/sixth requests;
- one successful consume under concurrency, one live challenge after concurrent issue, and generic public projection;
- default-off registration/activation/verification/delivery flags, absent public mutation routes, no token or protected identity data in public projections, and separate quiz eligibility.

Synthetic test addresses and a disposable PostgreSQL schema are used. The development `markado.public` schema, ignored `server/.env`, live identities, and assessment activation settings are outside this work.

## Still OPEN / blocked

- authoritative registrar/SIS owner, import contract, freshness, student-number canonicalization, duplicate/dispute resolution, and identity correction;
- approved institutional domains, mail provider, sender/DNS, application origin, monitoring/alerts, incident handling, and staging authorization;
- pending-claim creation timing, lost-mailbox/password recovery, and support ownership;
- D15 privileged grants, D19 identity/challenge/audit retention, and D23 exceptional assessment actions;
- any public registration, verification, resend, activation, or recovery route; protected-schema migration; deployment; assessment route/worker activation.
