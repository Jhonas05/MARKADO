# MARKADO hosted demo reset

The hosted presentation command is intentionally idempotent and
non-destructive. It refreshes the known synthetic account states and roster
freshness without deleting quiz, attempt, result, audit, or credential history.

## Routine restore

On the isolated staging environment, verify its Railway ID and database first,
then run through a private operator channel:

```text
php artisan markado:presentation provision --confirm=82eb52bf-06e4-4d19-b212-19d2bb652c09
php artisan markado:presentation status
```

This is safe to repeat. It refuses production, localhost, a mismatched HTTPS
URL, a different Railway environment identity, a non-PostgreSQL connection, an
unmigrated database, a conflicting guard marker, and wrong confirmation text.
It does not print or rotate the existing generated passwords.

Prepare a new quiz for each rehearsal or keep one clearly named synthetic quiz
as the fallback. Do not erase academic history just to make a screenshot look
clean.

## Fresh registration rehearsal

Once the configured registration roster identity has been claimed, do not
unlink or reassign it manually. That would undermine identity history. For a
fresh registration rehearsal, configure a new controlled demo mailbox and a
new synthetic Student number, then provision again only after confirming no
record uses either identifier.

## Full clean reset

There is no destructive hosted `migrate:fresh`, schema-drop, or bulk-delete
command. A fully clean reset must use a newly created isolated Railway staging
environment and its own new PostgreSQL service/volume:

1. Leave production and the existing presentation environment unchanged.
2. Create a new Railway environment with a new PostgreSQL service and volume.
3. Deploy the exact reviewed candidate and let additive migrations complete.
4. Set a new expected hosted environment ID/URL and synthetic identifiers.
5. Run `markado:presentation provision` with that new ID.
6. Verify `/up`, status, persistence after restart, email delivery, and the
   full browser workflow before presenting.

Deleting an old staging environment is a separate destructive operator action
and is not part of this procedure. Never point a reset at the production URL,
the production Railway environment, the production PostgreSQL service, or the
local protected `markado.public` schema.
