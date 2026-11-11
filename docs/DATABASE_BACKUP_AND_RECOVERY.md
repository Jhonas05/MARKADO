# MARKADO database backup and recovery

Status: recovery tooling rehearsal passed with synthetic loopback data on
12 November 2026; live staging and production backup gates remain open.

## Current capability and evidence

- Railway point-in-time recovery was reported disabled for both MARKADO
  environments during the 11 November inspection. No paid capability is
  assumed and no billing change is authorized.
- PostgreSQL 17.11 client tools are available on the controlled workstation.
- A non-destructive rehearsal created a fresh loopback cluster, inserted two
  synthetic probe rows, produced a custom-format dump with `--no-owner` and
  `--no-privileges`, restored into a different database, and verified the row
  count and deterministic content digest.
- Evidence is private at
  `C:\Users\stcla\Documents\MARKADO-QA\recovery-rehearsal\20261112-022130`.
  The dump is 1,832 bytes with SHA-256
  `5D33C0EB9541C54F6A60359A3284441348235336CA129BE58003FDB1BBCEC3D0`;
  the restored probe was
  `2|7370a9c775ccd3eee502f1a8cab59749`.
- This evidence validates the dump/restore mechanics only. It is not a backup
  of staging or production and contains no MARKADO account or academic data.

## Required policy before production

The owner must name an operational custodian and approve storage, retention,
privacy, breach response, and recovery objectives. The minimum proposed
baseline is one encrypted logical backup every 24 hours, seven daily copies,
four weekly copies, a monthly isolated restore rehearsal, and a restore before
every production schema release. Managed PITR should be added only when its
plan and billing are explicitly approved.

Backups must be stored outside the source database volume, encrypted at rest,
access-limited to the named custodian, and covered by access/audit logging. Do
not place dumps in Git, application public storage, build artifacts, chat, or
screenshots. Never print connection strings or passwords.

## Backup procedure

1. Confirm the source environment ID, service ID, database name, and current
   migration state through a private operator channel. Refuse ambiguous or
   production-looking targets during a staging drill.
2. Supply PostgreSQL connection settings through process-scoped `PG*`
   variables or an access-restricted `PGPASSFILE`; do not put a password on the
   command line.
3. Write to a new private directory and run:

   ```text
   pg_dump --format=custom --no-owner --no-privileges --file=<new-private-path> <database-name>
   pg_restore --list <new-private-path>
   ```

4. Record UTC time, source environment/service IDs, database name, PostgreSQL
   version, migration batch, byte size, SHA-256, operator, and storage object
   identifier. Record no secrets or row data.
5. Copy the encrypted artifact to the approved off-volume store and verify its
   checksum after transfer. A local-only dump is not a completed backup.

## Isolated restore rehearsal

1. Create a new disposable database named
   `markado_restore_rehearsal_<timestamp>` on loopback or a separately guarded
   recovery service. Never target an active staging or production database,
   `markado.public`, or an existing demo schema.
2. Confirm source and target host/database tuples differ.
3. Restore with:

   ```text
   pg_restore --exit-on-error --no-owner --no-privileges --dbname=<isolated-target> <private-dump>
   ```

4. Verify `migrations`, expected table counts, foreign-key constraints, role
   enum values, quiz/attempt/result relationships, and aggregate counts. Use
   hashes or counts in evidence; do not export names, emails, answers, or
   grades.
5. Start the exact candidate against the isolated target with public routes
   disabled and run read-only smoke checks.
6. Stop the isolated target. Deletion is a separate, explicitly scoped cleanup
   operation; never delete the source volume.

## Recovery decision

Production promotion is blocked until either (a) an encrypted live backup is
created and successfully restored into an isolated database, or (b) an
authorized owner explicitly accepts the residual recovery risk in writing.
The synthetic rehearsal does not satisfy that production gate.
