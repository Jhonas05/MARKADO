# MARKADO production release readiness

This runbook covers the production-capable onboarding and reporting implementation introduced on 11 November 2026. It deliberately contains no school identity, credentials, or demo passwords.

## Default state

Registration, assessment routes, assessment expiration, and gamification remain default-off. Deploying the code or running the additive roster-provenance migration does not activate any of them.

## Required identity inputs

The institution must provide and approve all of the following before Student registration can open:

- exact institutional email domain or domains;
- the identifier of the real authoritative roster feed;
- an approved roster freshness limit from 1 through 168 hours;
- imported roster rows carrying the exact source identifier and a fresh `roster_synced_at` timestamp;
- non-loopback SMTP delivery and a verified sender address;
- privacy, retention, dispute, recovery, and support ownership.

The relevant server settings are:

```text
MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED=true
MARKADO_STUDENT_SELF_REGISTRATION_ENABLED=true
MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT=5
MARKADO_INSTITUTIONAL_EMAIL_DOMAINS=<approved exact domains>
MARKADO_TRUSTED_ROSTER_SOURCE=<approved real source identifier>
MARKADO_TRUSTED_ROSTER_MAX_AGE_HOURS=<approved 1-168 value>
MAIL_MAILER=smtp
MAIL_HOST=<non-loopback SMTP host>
MAIL_FROM_ADDRESS=<verified sender>
```

Do not enable a flag until its value and external dependency have been verified. Registration readiness also requires `APP_ENV=production`, PostgreSQL, and an HTTPS non-loopback `APP_URL`.

## Staging gate

Use a separate Railway environment and PostgreSQL service. Populate it only with synthetic roster records whose `roster_source` matches the staging source identifier. Verify registration, delivered email, single-use/expiry/resend behavior, Student and Professor flows, exports, timer reconciliation, dark/light themes, and 1440 px, 768 px, and 390 px layouts.

For the hosted presentation environment, configure the exact Railway identity,
URL, controlled demo mailboxes, and synthetic roster source described in
`PRESENTATION_RUNBOOK.md`, then run:

```text
php artisan markado:presentation provision --confirm=<exact-environment-id>
php artisan markado:presentation status
```

The command is idempotent and writes generated passwords only to private
application storage. It refuses production, mismatched Railway identity/URL,
an unguarded database, and non-PostgreSQL runtimes.

Staging must prove that no route points to the production database and that the scheduler runs `php artisan schedule:run` once per minute before assessment expiration is enabled.

## Production gate

Before enabling production registration or assessment delivery:

1. Record a successful database backup and restore test.
2. Verify roster source ownership, freshness, and reconciliation behavior.
3. Verify transactional mail delivery, bounce/failure monitoring, and the public origin.
4. Verify scheduler ownership and timer reconciliation monitoring.
5. Complete authenticated browser QA with institution-approved test identities.
6. Confirm CI is green for the exact merge tree.
7. Enable only the reviewed variables, then re-run smoke tests without exposing credentials.

If any item is missing, keep the corresponding switches off and report the blocker. Never substitute synthetic production identities or a log mailer.

## Reporting behavior

The Professor results page supports score high-to-low and student A-to-Z views. PDF and DOCX exports use the same owner-scoped data and do not release grades. The Student view still exposes only released earned/maximum points and never answer keys or item-level correctness.
