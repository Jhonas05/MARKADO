# MARKADO full MVP implementation checklist

Status is evidence-based as of 11 November 2026. `IMPLEMENTED` does not mean a
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

## Operationally ready but default-gated

- Production registration code requires a real institutional domain, named
  authoritative roster feed, freshness policy, remote SMTP, and verified sender.
- Hosted presentation registration requires controlled synthetic inboxes,
  remote SMTP credentials, an exact Railway staging identity, and a database
  guard marker.
- Assessment routes require explicit staging/production activation. Background
  expiration additionally requires one observed once-per-minute scheduler.

## External or evidence blockers

- No institution-approved school domain, roster authority, privacy/retention/
  recovery ownership, or transactional-mail credentials have been supplied.
- No named scheduler owner or backup/restore drill evidence is available.
- Actual rendered browser, mobile, light/dark, keyboard, and accessibility QA is
  not verified because no safe browser session is currently available.
- GitHub PR creation/merge remains blocked until an authorized `Jhonas05`
  GitHub connector/session or an explicitly selected connected account with
  repository write permission is available.

## Release boundary

Keep production registration, assessments, expiration, and gamification off
until their corresponding gates pass. Never migrate/reset `markado.public`,
merge PR #24, use real students as demo data, or claim presentation readiness
without delivered-email and browser evidence.
