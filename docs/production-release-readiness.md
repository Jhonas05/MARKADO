# MARKADO production release readiness

This runbook covers the production-capable onboarding and reporting implementation introduced on 11 November 2026. It deliberately contains no school identity, credentials, or demo passwords.

## Default state

Registration, assessment routes, assessment expiration, and gamification remain default-off. Deploying the code or running the additive roster-provenance migration does not activate any of them.

## Self-service Student accounts

The owner approved account creation for a new Student without a roster row or invitation. With `MARKADO_STUDENT_CLAIM_SIGNUP_ENABLED=true`, an approved school mailbox can activate one Student account after verification. The entered number is an encrypted claim, with no uniqueness rule on the number. Only a separately authorized school record and quiz assignment can grant assessment access. Legacy roster-based pending accounts can still complete verification with their trusted roster settings in place.

Production prerequisites for this signup mode:

1. Release the reviewed candidate and apply the additive `2026_11_18_120000_create_student_signup_claims` migration to the authorized PostgreSQL database. Provision the normal Laravel database and session permissions. Keep a stable, protected `APP_KEY` because the claims and verification state depend on it.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://markado-app-production.up.railway.app`, and secure session cookies. Verify the trusted HTTPS ingress supplies the expected origin. Use the same schema and application key across application instances.
3. Configure the settings below with the approved exact school email domains, a sender verified by Resend, and an authorized private `RESEND_KEY`. Self-service stores the lowercase mailbox, so the school must confirm that the allowed mailboxes use case-insensitive addresses. Prove actual delivery, single use, and automatic activation with an institution-approved test mailbox before opening signup to Students.
4. After release authorization, enable the signup flags including `MARKADO_STUDENT_CLAIM_SIGNUP_ENABLED`. Rebuild any Laravel config and route caches with the final configuration and restart the approved web release. Verify the link on Landing and Login, account activation and login, staff-role denial, and quiz denial without academic verification and assignment. Keep assessment settings under their separate authorization.

The roster source and freshness settings below apply to the legacy roster-first registration mode. They are unnecessary for email-only account activation and must still be configured wherever the school requires them for academic identity workflows. If signup needs to close, turn off `MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED` and rebuild the caches. Retain the additive claim table and existing accounts.

For local browser QA, use a migrated, guarded synthetic PostgreSQL schema and a loopback Mailpit inbox. Start the local app with the claim-signup, presentation registration, verification, delivery, and activation switches enabled. Run `node server/tests/Browser/student-signup.mjs` with `MARKADO_CHROME` pointing to Chrome and `MARKADO_PLAYWRIGHT_MODULE` pointing to a local `playwright-core` installation if it is not otherwise resolvable. Defaults are the app on `127.0.0.1:8037` and Mailpit on `127.0.0.1:8025`. These can be overridden with `MARKADO_SIGNUP_QA_URL` and `MARKADO_SIGNUP_QA_MAILPIT`. The script creates a fresh synthetic account in the isolated schema.

## Legacy roster-first identity inputs

The institution must provide and approve all of the following before Student registration can open:

- exact institutional email domain or domains;
- the identifier of the real authoritative roster feed;
- an approved roster freshness limit from 1 through 168 hours;
- imported roster rows carrying the exact source identifier and a `roster_synced_at` timestamp within the approved freshness window and no later than the current server time;
- approved HTTPS transactional delivery and a verified sender address;
- privacy, retention, dispute, recovery, and support ownership.

Use these shared server settings for verified signup. The claim switch selects self-service. For roster-first signup, keep the claim switch off and configure the trusted roster source and age.

```text
MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED=true
MARKADO_STUDENT_CLAIM_SIGNUP_ENABLED=true
MARKADO_STUDENT_SELF_REGISTRATION_ENABLED=true
MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT=5
MARKADO_INSTITUTIONAL_EMAIL_DOMAINS=<approved exact domains>
MARKADO_TRUSTED_ROSTER_SOURCE=<approved real source identifier, where required>
MARKADO_TRUSTED_ROSTER_MAX_AGE_HOURS=<approved 1-168 value, where required>
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_DRIVER=resend-http
RESEND_KEY=<deployment secret>
MAIL_FROM_ADDRESS=<verified sender>
```

Do not enable a flag until its value and external dependency have been verified. Registration readiness also requires `APP_ENV=production`, `APP_DEBUG=false`, PostgreSQL, an HTTPS non-loopback `APP_URL`, and requests to its exact host. The Resend HTTPS adapter is the recommended transport. If the Laravel SMTP transport is used instead, its explicit remote hostname, `smtps` scheme, username, and password must be configured; an overriding `MAIL_URL` is rejected. Configuration readiness does not prove that the provider has delivered a message.

PR #36 added the default-off production gate for roster-first signup. Landing and Login advertise signup when Laravel has loaded the registration route and the request meets the configured origin and readiness checks. Local Mailpit delivery and mocked Resend tests establish local behavior and adapter coverage. A hosted release requires actual delivery evidence from the approved sender and mailbox.

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

The 12 November 2026 candidate deployment
`810d7197-ee9c-4f83-be54-c1dcc791f198` passed a real authenticated staging HTTP
assessment smoke run, including result withholding/release and PDF/DOCX
exports. This proves the guarded backend workflow but does not satisfy the
email-delivery, scheduler, backup, or rendered-browser gates below.

Staging must prove that no route points to the production database and that one
one-replica service runs `php artisan schedule:work` against the shared database
cache before assessment expiration is enabled. Railway native cron cannot meet
the one-minute requirement. Observe aggregate reconciliation logs across
multiple minutes and one real expired synthetic attempt before activation.

## Production gate

Before enabling production registration or assessment delivery:

1. Record an encrypted live database backup and isolated restore test. The
   12 November loopback synthetic rehearsal proves mechanics only.
2. Verify roster source ownership, freshness, and reconciliation behavior.
3. Verify transactional mail delivery, bounce/failure monitoring, and the public origin.
4. Verify scheduler ownership and timer reconciliation monitoring.
5. Complete authenticated browser QA with institution-approved test identities.
6. Confirm CI is green for the exact merge tree.
7. Enable only the reviewed variables, then re-run smoke tests without exposing credentials.

If any item is missing, keep the corresponding switches off and report the blocker. Never substitute synthetic production identities or a log mailer.

## Reporting behavior

The Professor results page supports score high-to-low and student A-to-Z views. PDF and DOCX exports use the same owner-scoped data and do not release grades. The Student view still exposes only released earned/maximum points and never answer keys or item-level correctness.
