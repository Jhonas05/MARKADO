# ADR-012 — V9 isolated-demo character rewards

- **Status:** APPROVED for an isolated demonstration only; production policy remains unapproved and default-off
- **Decision recorded:** 26 October 2026 (Asia/Manila)
- **Decision authority:** MARKADO product owner
- **Scope:** Cosmetic MARKADO Coins, original characters, shop purchases, collection, and equip state

## Decision

1. MARKADO Coins are a separate virtual currency. They do not read from, write to, multiply, or otherwise affect academic `earned_points`, `maximum_points`, timing, eligibility, or result release.
2. In the approved demo policy, one eligible terminal quiz attempt earns exactly 10 coins once, independent of score, correctness, speed, or ranking.
3. Eligibility requires a genuine server-finalized Student attempt with at least one persisted, non-null, server-acknowledged answer. An entirely unanswered expired attempt earns no coins.
4. Refresh, resume, preflight, repeated submit, repeated worker reconciliation, and competing finalization requests cannot create a second reward for the same attempt.
5. Byte is the free starter character. Mochi costs 10 coins and Pixel costs 20 coins. These values are environment configuration for the isolated demo, not school-wide policy.
6. Characters are cosmetic only. Ownership and equipped state confer no quiz, grade, time, access, or ranking advantage.
7. Purchases use the trusted server catalog price and a PostgreSQL transaction that locks the Student wallet. A purchase cannot create a negative balance, buy the same character twice, debit another Student, or overspend through competing requests.
8. Every coin award and shop purchase creates an auditable ledger entry. Each reward also has a unique attempt-reward record; each ownership has a unique Student/character constraint.
9. Equip operations require ownership in the authenticated Student's collection. The browser cannot supply a wallet balance, price, owner, reward amount, or Student identifier.
10. Gamification routes and reward processing are enabled only when the feature flag is on **and** the runtime is automated testing or a loopback local database using an owned `markado_demo_v89_*` schema guard. Normal and production defaults remain off.

## Why

The policy rewards participation without changing academic outcomes or encouraging rushed answers. A 10-coin reward makes Mochi demonstrable after one eligible completion while Pixel demonstrates accumulation after two. The free starter gives every provisioned demo Student a usable collection before earning currency.

## Correctness and privacy consequences

- Wallet balance is the serialized current balance; the append-only ledger is the audit trail. Wallet mutation, ledger insertion, reward/purchase record creation, and ownership mutation share one transaction.
- Row locks serialize operations for one wallet. Database constraints reject negative stored balances, duplicate attempt rewards, and duplicate ownership.
- The submission receipt may disclose the independent coin award but never includes an unreleased academic score or answer key.
- The existing manual Professor release boundary and objective scoring rules remain unchanged.

## Synthetic acceptance tests

- Correct and incorrect eligible completions receive the same 10 coins.
- Replayed and concurrent finalization creates one reward and one ledger entry.
- Entirely unanswered expiration creates no reward.
- Affordable purchase debits once and creates one ownership; duplicate and insufficient-balance purchases fail without debit.
- Concurrent purchases sharing one insufficient wallet cannot both succeed.
- Student-only route authorization and ownership checks reject cross-role and cross-Student actions.
- Equipped state persists across session logout/login and appears only in safe Student props.
- With the flag off or outside a guarded isolated runtime, routes return unavailable and finalization makes no reward mutation.

## Exclusions and revisit criteria

This ADR does not authorize production enablement, school-wide reward values, real money, payments, trading, gifts, random rewards, loot boxes, rankings, streak penalties, score/speed bonuses, public registration, deployment, or PR merge. Revisit before any institutional activation, economy value change, additional earning source, transfer mechanism, or academic integration.
