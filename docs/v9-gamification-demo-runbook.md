# MARKADO V9 gamification demo runbook

## Safety boundary

Use only a loopback PostgreSQL database and a guarded schema named `markado_demo_v89_*`. Never point this flow at `public`. Normal and production defaults remain disabled.

Process-scoped demo values:

```text
MARKADO_GAMIFICATION_ENABLED=true
MARKADO_GAMIFICATION_COMPLETION_REWARD=10
MARKADO_GAMIFICATION_BYTE_PRICE=0
MARKADO_GAMIFICATION_MOCHI_PRICE=10
MARKADO_GAMIFICATION_PIXEL_PRICE=20
```

With those variables and the existing local database variables set, provision with:

```text
php artisan markado:demo provision --schema=markado_demo_v89_gamified
```

The guarded command migrates only that schema, synchronizes the three-character catalog, creates synthetic Professor/Student identities, grants Byte once, and stores synthetic credentials in the ignored private storage path it reports.

## Demonstration path

1. Complete the ordinary Professor create/questions/publish/assignment flow.
2. Sign in as the synthetic Student, join the assigned quiz, save at least one answer, and reach a server-confirmed terminal submission.
3. Confirm the receipt shows `+10 MARKADO Coins` without an unreleased academic score.
4. Open Shop, purchase Mochi for 10 coins, and confirm the balance becomes zero.
5. Open Collection, equip Mochi, sign out, sign in again, and confirm Mochi remains equipped.
6. Confirm Pixel remains unavailable with an insufficient balance and repeated purchase of Mochi is denied.
7. Release the academic result separately as the owning Professor and confirm the Student then sees only the approved score/total projection.

## Wallet correctness

The server ignores client prices and balances. Reward and purchase services lock the authoritative wallet row, update its nonnegative balance, and insert ledger plus reward/ownership records in one transaction. Unique database constraints enforce one reward per attempt and one ownership per Student/character. Equip checks ownership for the authenticated Student.

Cleanup remains guarded and requires the exact schema name:

```text
php artisan markado:demo cleanup --schema=markado_demo_v89_gamified --confirm=markado_demo_v89_gamified
```
