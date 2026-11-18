# MARKADO full MVP implementation checklist

Status is evidence-based as of 12 November 2026. `IMPLEMENTED` does not mean a
feature is enabled in production.

## Implemented and tested in code

- Laravel/PostgreSQL/React-Inertia role authentication and active-account
  routing for Student, Professor, and Academic Administrator.
- Exact roster-backed Student registration, hash-only single-use mailbox
  challenges, automatic Student-only activation, and duplicate/concurrency
  controls behind fail-closed environment gates.
- Owner-scoped quiz draft authoring, MCQ/True-False questions, immutable
  publication, scheduling, stable randomization, and direct Student assignment.
- Server-authoritative one-attempt flow, acknowledged answer saves, deadline
  reconciliation, objective scoring, manual Professor release, and minimal
  Student result projection.
- Deterministic Professor result ordering and owner-authorized PDF/DOCX exports.
- Read-only Administrator operational summary and authenticated role-aware Help.
- Guarded local demo provisioning plus guarded hosted-presentation provisioning
  for a separate staging environment and database.
- Resend HTTPS institutional-verification adapter with bearer authentication,
  idempotency, bounded timeouts/retry, privacy-safe failures, and fail-closed
  hosted readiness checks.
- Once-per-minute expiration schedule with shared leader/overlap locks and
  aggregate privacy-safe run logging; database finalization remains repeat-safe.

## Operationally ready but default-gated

- Production registration code requires a real institutional domain, named
  authoritative roster feed, freshness policy, approved provider key, and
  verified sender.
- Hosted presentation registration requires controlled synthetic inboxes,
  Resend HTTPS credentials, an exact Railway staging identity, and a database
  guard marker.
- Assessment routes require explicit staging/production activation. Background
  expiration additionally requires one observed once-per-minute scheduler.

## External or evidence blockers

- No institution-approved school domain, roster authority, privacy/retention/
  recovery ownership, Resend key, or verified sender has been supplied.
- A synthetic local dump/restore rehearsal passed, but there is no encrypted
  live staging/production backup, isolated live-data restore, or named owner.
- No paid/authorized one-replica Railway scheduler service is available, so
  real once-per-minute logs and expired-attempt evidence remain missing.
- Actual rendered browser, mobile, light/dark, keyboard, and accessibility QA is
  not verified because no safe browser session is currently available.
- PR #36 exists and remains the only feature PR. Updating its body through the
  connected GitHub app requires an explicitly selected write account.

## Release boundary

Keep production registration, assessments, expiration, and gamification off
until their corresponding gates pass. Never migrate/reset `markado.public`,
merge PR #24, use real students as demo data, or claim presentation readiness
without delivered-email and browser evidence.

## Latest local verification

- Laravel/SQLite: 212 tests, 1,867 assertions passed.
- Laravel/PostgreSQL 17: 212 tests, 2,039 assertions passed, including the
  concurrency groups.
- React/Vitest: 47/47 tests passed; ESLint and both Vite production builds
  passed; Laravel Pint passed.
- Root and server production npm audits: zero vulnerabilities.
- Composer audit: zero advisories after upgrading the affected Symfony HTTP
  components to 7.4.23.
- No `.env`, credential JSON, database dump, SQL dump, or private QA artifact is
  tracked. The ignored environment files were not changed.
- Candidate `c67c772` deployed successfully to isolated Railway staging as
  deployment `810d7197-ee9c-4f83-be54-c1dcc791f198`; production was unchanged.
- Real authenticated staging HTTPS smoke passed role login/logout, quiz
  publish and direct assignment, Student preflight/save/advance/submit,
  repeated-submit idempotence, withheld/released score flow, and PDF/DOCX
  exports. This is backend HTTP evidence, not rendered browser QA.
