# MARKADO final release handoff

Evidence date: 12 November 2026, Asia/Manila.

## Candidate and GitHub

- Repository: `Jhonas05/MARKADO`
- Existing PR: `#36`, branch `feat/full-live-mvp-production-readiness`
- Implementation commit: `ee6363c` (`feat: add HTTPS verification delivery
  and scheduler safeguards`)
- PR #24 was not modified, merged, or deployed.
- `markado.public` was not migrated, reset, or otherwise modified.

The final documentation commit and exact GitHub checks must be read from the
branch head after push. Do not merge while any external gate below remains
open.

## Implementation delivered

- A production-capable Resend HTTPS verification adapter with bearer
  authentication, deterministic idempotency, bounded timeouts and retry,
  provider-acceptance monitoring, privacy-safe failure logs, and fail-closed
  handling.
- Hosted readiness selects the adapter explicitly and refuses missing API key,
  invalid sender, unsafe headers, missing application key, unapproved driver,
  insecure public origin, stale/unnamed roster source, or disabled identity
  controls. The guarded loopback demo remains pinned to Laravel mail and local
  SMTP.
- Existing single-use, hash-at-rest, 30-minute expiry, 60-second cooldown,
  five-per-hour, replay, enumeration, roster, role, and atomic activation rules
  remain in force.
- The once-per-minute assessment event has a shared-cache leader lock,
  two-minute overlap lock, PostgreSQL row serialization, repeat-safe
  finalization, and aggregate operational logs without Student/answer content.
- Symfony HTTP components were upgraded to patched 7.4.23 releases after a
  current Composer audit identified six advisories in 7.4.21.

## Verification results

- Laravel/SQLite: 212 tests, 1,867 assertions passed.
- Laravel/PostgreSQL 17: 212 tests, 2,038 assertions passed, including real
  concurrency processes.
- Resend/readiness/scheduler focused regression: 8 tests, 32 assertions passed.
- React/Vitest: 47/47 passed.
- ESLint, Laravel Pint, root Vite build, and Laravel/Inertia Vite build passed.
- Root and server production npm audits: zero vulnerabilities.
- Composer audit after upgrade: zero advisories.
- High-signal tracked-file scan found no private key, provider key, embedded
  PostgreSQL credential URL, credential JSON, dump, SQL export, or private QA
  artifact. `.env.example` is intentionally tracked; real `.env` files remain
  ignored and unchanged.

Local PHPUnit reports warnings because this worktree deliberately has no
ignored `.env`; tests use a process-scoped synthetic `APP_KEY`. CI creates its
own ephemeral `.env` and key. The warnings did not represent failed assertions.

## Database and recovery

All migrations passed on a fresh isolated PostgreSQL 17 database. A separate
loopback recovery rehearsal produced a custom-format dump, restored it into a
different database, and matched a two-row integrity probe. Evidence and the
dump remain private under `C:\Users\stcla\Documents\MARKADO-QA`.

This is not a live MARKADO backup. Railway PITR is disabled and neither an
encrypted staging/production backup nor a live-data restore has been verified.
See `DATABASE_BACKUP_AND_RECOVERY.md`.

## Staging and production

- Staging URL: `https://markado-app-staging.up.railway.app`
- Last independently verified staging deployment before this candidate:
  `602ea106-4082-44b6-bb0b-214fe74f78ea`, source `221a4af`.
- Production URL: `https://markado-app-production.up.railway.app`
- Production remains unchanged at the previously observed deployment
  `d349762c-5421-4589-9fe1-36a3e99eaf52`.
- On 12 November, staging `/`, `/register`, and `/up` returned 200; production
  `/up` returned 200. Staging returned 404 for `/.env` and `/backup.sql` and 403
  for the probed private credential-storage path.

The latest candidate is not yet deployed. The connected Railway accounts do
not expose project `be241212-40d8-43f4-abf2-04efb84d094f`, so staging deploy,
variable changes, live logs, scheduler service creation, and managed backup
configuration could not be performed through the current connection.

## Presentation environment

The isolated staging database already has the guarded synthetic Professor,
Student, Administrator, and roster dataset. The private presentation credential
artifact contains the approved account identifiers and passwords; passwords
must not be copied into Git, this handoff, chat, or screenshots. The previously
verified real HTTP assessment path covers quiz creation, questions, schedule,
publish, assignment, Student attempt/save/submit, 2/2 scoring, withholding,
Professor release, Student disclosure, and PDF/DOCX exports.

Registration cannot be presented as complete: no authorized Resend API key,
provider-verified sender, or controlled live delivery evidence is available.
Background expiration also remains off because no authorized one-replica
`schedule:work` service and multi-run logs exist.

## Browser QA

**BROWSER QA NOT VERIFIED.** The approved browser runtime returned
`No browser is available` and listed no browser. No screenshots or rendered QA
passes are claimed. See `BROWSER_QA_REPORT.md` for the required 1440/768/390,
light/dark, keyboard, accessibility, and end-to-end matrix.

## Required external inputs

1. A Resend API key stored as a Railway secret, a provider-verified sender, and
   an authorized controlled recipient mailbox.
2. The correct Railway account connection for the MARKADO project and explicit
   approval for any additional billed scheduler service.
3. An encrypted live backup destination, named recovery owner, retention/RPO/
   RTO approval, and isolated live backup restore evidence.
4. An available approved browser runtime for real responsive and E2E QA.
5. For production only: approved institutional domain, real roster authority
   and freshness, privacy/recovery/support ownership, review, exact-tree green
   CI, and explicit final promotion authorization.

## Verdicts

- Presentation readiness: **BLOCKED** by real email delivery and rendered
  browser QA. The pre-provisioned fallback Student can demonstrate the academic
  flow only if the presenter explicitly states registration is unverified.
- Production release readiness: **BLOCKED** by institutional identity inputs,
  live recovery protection, scheduler evidence, browser QA, review, deployment,
  and final promotion authorization.
