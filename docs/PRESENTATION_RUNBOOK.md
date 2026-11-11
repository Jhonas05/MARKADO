# MARKADO presentation runbook

This runbook is for the isolated hosted presentation environment at
`https://markado-app-staging.up.railway.app`. It is not the production database
or the `markado.public` local schema. Do not use this runbook until the pre-demo
checks below pass.

## Current readiness boundary

The staging application and separate PostgreSQL service exist. The guarded
dataset has been provisioned with three synthetic role accounts and two
synthetic roster rows. Login and the real Professor-to-Student HTTP assessment
flow have been verified. Registration remains fail-closed because no authorized
Resend API key and verified sender are configured. Account passwords are stored
only in the private application storage path reported by the command; they are
never committed or printed.

The presentation environment ID is
`82eb52bf-06e4-4d19-b212-19d2bb652c09`. Confirm the live Railway environment
still has this exact ID before using it. If it differs, stop and review the
environment rather than changing the expected value blindly.

## One-time hosted configuration

Set these variables only on the isolated staging environment. Values in angle
brackets are required operator inputs, not defaults.

```text
APP_ENV=staging
APP_URL=https://markado-app-staging.up.railway.app
MARKADO_HOSTED_PRESENTATION_ENABLED=true
MARKADO_HOSTED_PRESENTATION_URL=https://markado-app-staging.up.railway.app
MARKADO_HOSTED_PRESENTATION_ENVIRONMENT_ID=82eb52bf-06e4-4d19-b212-19d2bb652c09
MARKADO_HOSTED_PRESENTATION_ENVIRONMENT_NAME=staging
MARKADO_PRODUCTION_URL=https://markado-app-production.up.railway.app

MARKADO_INSTITUTIONAL_EMAIL_DOMAINS=<controlled-demo-domain>
MARKADO_TRUSTED_ROSTER_SOURCE=<synthetic-roster-source-id>
MARKADO_TRUSTED_ROSTER_MAX_AGE_HOURS=24
MARKADO_PRESENTATION_ADMIN_EMAIL=<synthetic-admin-email>
MARKADO_PRESENTATION_PROFESSOR_EMAIL=<synthetic-professor-email>
MARKADO_PRESENTATION_FALLBACK_STUDENT_EMAIL=<synthetic-active-student-email>
MARKADO_PRESENTATION_FALLBACK_STUDENT_NUMBER=<synthetic-active-student-number>
MARKADO_PRESENTATION_REGISTRATION_EMAIL=<controlled-registration-inbox>
MARKADO_PRESENTATION_REGISTRATION_STUDENT_NUMBER=<synthetic-registration-number>

MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_DRIVER=resend-http
RESEND_KEY=<deployment-secret-from-approved-provider>
MAIL_FROM_ADDRESS=<provider-verified-sender>
MAIL_FROM_NAME=MARKADO
```

The two Student emails must use the configured demo institutional domain. All
four emails and both Student numbers must be distinct. Do not use a real
student identity.

After a verified staging deployment has applied migrations, run from a private
Railway shell or equivalent operator channel:

```text
php artisan markado:presentation provision --confirm=82eb52bf-06e4-4d19-b212-19d2bb652c09
php artisan markado:presentation status
```

Retrieve the generated credential file through the same private operator
channel. Do not paste it into Git, tickets, screenshots, chat, or logs. Record
only the configured email identifiers in presenter notes.

## Registration and assessment activation

Registration remains unavailable until all of the following are verified:

- the Resend HTTPS API key and a provider-verified sender are configured;
- the presenter can access the controlled registration inbox;
- the provisioned synthetic roster record is fresh and unclaimed;
- `/up` is healthy over HTTPS;
- the hosted presentation status command reports a verified guard;
- the database is the staging PostgreSQL service, not production.

Then enable only in staging:

```text
MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED=true
MARKADO_STUDENT_SELF_REGISTRATION_ENABLED=true
MARKADO_STUDENT_AUTO_ACTIVATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_ENABLED=true
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_DELIVERY_DRIVER=resend-http
MARKADO_INSTITUTIONAL_EMAIL_VERIFICATION_HOURLY_LIMIT=5
MARKADO_ASSESSMENT_ROUTES_ENABLED=true
```

Keep `MARKADO_ASSESSMENT_EXPIRATION_ENABLED=false` until one named, one-replica
`php artisan schedule:work` runner is operating once per minute against the
shared database cache and its aggregate logs are observed across several runs.
Railway native cron is unsuitable because its minimum interval is five minutes.
Request-time deadline enforcement remains authoritative, but a presentation
must not claim background expiration coverage while this switch is off.

## Required pre-demo checks

1. Verify the staging URL, Railway environment ID, app service, PostgreSQL
   service, and volume all belong to staging.
2. Run `markado:presentation status`; expect a verified guard, three synthetic
   role accounts, two synthetic roster records, and the intended route gates.
   Presenter account identifiers come from the private credential artifact;
   never copy its passwords into this runbook or screenshots.
3. Confirm the credential file survives an application restart without
   revealing its contents in logs.
4. Send and consume one fresh verification message through the controlled
   inbox. Confirm replay fails and the Student activates automatically.
5. Complete the Professor-to-Student assessment workflow and result release.
6. Open and validate one PDF and one DOCX export.
7. Complete the browser matrix at 1440, 768, and 390 pixels in light and dark
   themes, including keyboard Sign out.
8. Re-run automated tests and record the exact candidate SHA and CI URL.

## Nine-minute demonstration

### 0:00-1:00 — introduction

- Open the staging landing page over HTTPS.
- Explain that MARKADO is Laravel/React/Inertia backed by isolated PostgreSQL,
  with server-authoritative identity, eligibility, timers, saves, and grading.

### 1:00-2:00 — Student registration

- Open Register and enter the configured synthetic registration Student number
  and its controlled mailbox.
- Submit a presenter-chosen password that is not reused elsewhere.
- Open the delivered message privately, enter the one-time code, and show
  automatic Student-only activation and the Student Dashboard.

### 2:00-4:00 — Professor workspace

- Sign out, then sign in with the synthetic Professor identifier and its
  private generated password.
- Create or open a prepared quiz, add MCQ/True-False questions, configure the
  Asia/Manila schedule and timers, publish, and assign the registered Student.
- Copy the generated code without displaying answer keys after leaving the
  authoring page.

### 4:00-6:00 — Student assessment

- Sign in as the registered Student, join by code, read the instructions, and
  start.
- Answer several questions while pointing out the server-confirmed save state
  and persisted timers. Submit and show the receipt without an academic score.

### 6:00-7:00 — results and exports

- Return to the Professor, open Results, compare score and A-to-Z ordering,
  release the Student result, and download PDF and DOCX.
- Return to the Student and show only the released earned/maximum points.

### 7:00-8:00 — Administrator and help

- Sign in with the synthetic Administrator identifier and its generated
  password. Show only the read-only, permission-scoped operational summary.
- Open Help & Guide and show its role-aware instructions.

### 8:00-9:00 — close

- Summarize the connected workflow and the separation between staging and
  production.
- State any unverified item honestly, especially email delivery, scheduler,
  browser/accessibility coverage, backup restore, or production activation.

## Expected results

- Registration creates a pending Student only for the exact unclaimed roster
  record; mailbox proof plus fresh roster checks activates it once.
- Quiz code resolution never bypasses assignment, publication, or schedule.
- Refresh returns the same attempt, question order, answers, and deadlines.
- Submission is idempotent, score is server-computed, and the Student sees no
  score until Professor release.
- Exports contain the same owner-scoped ordered results and no answer keys.

## Common recovery steps

- Registration page informational only: re-run the status command and inspect
  the hosted guard, Resend key/sender, roster, and five registration switches.
- Verification not delivered: leave the Student pending, inspect provider
  delivery logs privately, and do not bypass the code.
- Quiz unavailable: verify publish state, current Asia/Manila window, and the
  exact Student assignment.
- Save not confirmed: stop claiming it was saved, restore connectivity, and
  reload the authoritative attempt.
- Export fails: verify Professor ownership, assessment route gate, and runtime
  logs; never use a frontend-only substitute.

## Safe fallback

If registration email is unavailable, use the pre-provisioned fallback Student
only to demonstrate the academic workflow and explicitly say registration
delivery is not verified. If staging itself is unhealthy, use the guarded local
PostgreSQL demo in `v8-9-functional-assessment-demo-runbook.md`; do not switch
the presentation to production or expose a local Mailpit service publicly.
