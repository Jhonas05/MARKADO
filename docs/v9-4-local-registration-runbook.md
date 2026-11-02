# v9.4 local Student registration acceptance

This slice adds a real four-field Student signup and mailbox/roster activation flow under ADR-013. All switches default off. No migration is added; existing unique Student number, roster email, roster/user, and user/email constraints are reused.

## Runtime boundary

Only a guarded local PostgreSQL schema named `markado_demo_v89_registration_*` may enable the presentation flow. The application, database, and SMTP inbox must use loopback. SMTP URL overrides and log transport are refused. Automated tests use in-memory SQLite or disposable PostgreSQL schemas.

Required process-only values:

```text
APP_ENV=local
APP_URL=http://127.0.0.1:8016
DB_SCHEMA=markado_demo_v89_registration_acceptance
SESSION_COOKIE=markado_v94_registration_session
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MARKADO_PRESENTATION_REGISTRATION_ENABLED=true
MARKADO_STUDENT_SELF_REGISTRATION_ENABLED=true
MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT=5
MARKADO_INSTITUTIONAL_EMAIL_DOMAINS=students.example.edu
MARKADO_ASSESSMENT_ROUTES_ENABLED=true
MARKADO_ASSESSMENT_EXPIRATION_ENABLED=false
MARKADO_GAMIFICATION_ENABLED=true
```

Use an independently approved synthetic roster setup before registration. The signup route never creates or approves school roster evidence. The existing `markado:demo` command can prepare the isolated base schema; do not use it to overwrite an existing presentation. Mailpit's SMTP and inbox listeners must bind explicitly to `127.0.0.1:1025` and `127.0.0.1:8025`, with no external relay/forward configuration. The local prepared launcher and evidence live outside Git in `Documents/MARKADO-QA/v9.4-registration`.

## Normal Student workflow

1. From Sign in, choose **Create a Student account**. Enter the exact official Student Number, authorized institutional Email, Password, and Confirm Password. No full name or role is requested.
2. Use at least 12 characters with uppercase/lowercase letters and a number. Laravel hashes the password. Validation errors identify fields; rejected identity/account claims share a generic verification acknowledgement.
3. Open the local Mailpit inbox, read the email for that synthetic Student, copy its full one-time code, and paste it into **Verification code**. Keep codes and credentials off screenshots/projectors. Confirm verification.
4. After mailbox proof and fresh roster/security checks succeed, the pending Student activates atomically. Sign in and use the dashboard.
5. A published quiz remains unavailable until the Professor assigns that verified roster identity. The Professor may prepare an assignment before signup or assign the active Student afterward.
6. Join, Start, save answers, submit, earn the one-time cosmetic reward, purchase/equip Mochi, and have the owning Professor release the academic result separately.

## Recovery and limits

- Existing numbers/accounts are never reassigned or given a new password by signup. Sign in with the original password to resume a pending verification session. Public password/account recovery remains unavailable.
- Resend waits 60 seconds and shares a maximum five issues per rolling hour. Failed deliveries still count; a new code revokes the old one. Codes expire after exactly 30 minutes.
- Pending, disabled, disputed, inactive, wrong-domain, wrong-roster, foreign-account, and unassigned states fail closed. An unavailable mailbox/roster/security service cannot activate anyone.
- If an unknown person reserved a pending identity or the original password/mailbox is lost, use the school's legitimate support process. This release does not invent ownership recovery or roster correction authority.
- Real school activation still needs an authorized roster source/maintainer and freshness policy, actual institutional domains, approved external email provider/origin/DNS, monitoring/retention/support rules, and separate operational activation approval.

## Verification

Run PostgreSQL Laravel and concurrency suites on a named disposable `markado_test_*` schema; run SQLite regressions, Pint, ESLint, Vitest, both builds, audits, and secret scans. Browser acceptance must include actual SMTP delivery, code consumption, automatic activation, both assignment orderings, unassigned denial, and the assessment/reward/manual-release flow. Preserve all v9.3 schemas, wallets, results, credentials, and fallback screenshots.
