# MARKADO v8.9 local functional assessment demo

This runbook uses a synthetic, command-owned PostgreSQL schema. It never runs
migrations, seeds, resets, or truncation against `markado.public`. The normal
assessment and expiration defaults remain disabled.

## 1. Prepare the isolated schema and synthetic identities

From `server/`, keep the existing ignored `.env` unchanged and run:

```powershell
php artisan markado:demo provision --schema=markado_demo_v89_local
```

The command verifies a local/test Laravel environment, PostgreSQL over
loopback, the configured database name, and the required `markado_demo_v89_*` schema name before it creates
or migrates anything. It refuses to adopt a pre-existing schema unless that
schema contains its exact ownership marker. It provisions one active Professor, one active Student,
the Student's verified synthetic institutional mailbox, and a linked verified
synthetic roster record. It creates no Administrator account.

Credentials are generated locally and written only to this ignored private
file; they are never printed or committed:

`server/storage/app/private/markado-demo/markado_demo_v89_local/credentials.json`

## 2. Build and start the local application

Build the Laravel/Inertia assets once:

```powershell
npm run build
```

In the same PowerShell process that will run Laravel, enable only the isolated
demo schema and assessment routes:

```powershell
$env:APP_URL = 'http://127.0.0.1:8000'
$env:DB_SCHEMA = 'markado_demo_v89_local'
$env:MARKADO_INSTITUTIONAL_EMAIL_DOMAINS = 'students.example.edu'
$env:MARKADO_ASSESSMENT_ROUTES_ENABLED = 'true'
$env:MARKADO_ASSESSMENT_EXPIRATION_ENABLED = 'false'
php artisan serve --host=127.0.0.1 --port=8000
```

Do not start a queue worker or scheduler for this walkthrough. Request-time
deadline enforcement remains authoritative.

## 3. Professor: create and publish

1. Open `http://127.0.0.1:8000/login` and use the Professor credentials from
   the private file.
2. Create a quiz, add at least one MCQ or True/False question, and save an
   opening/closing window that includes the current Asia/Manila time.
3. Configure a whole-quiz timer and optional per-question timer.
4. Publish the quiz and copy its eight-character access code.

## 4. Assign the synthetic Student

In another PowerShell terminal, from `server/`, run:

```powershell
php artisan markado:demo assign --schema=markado_demo_v89_local --quiz-code=PASTE_CODE_HERE
```

The command accepts only a quiz owned by the synthetic Professor. It exposes no
public roster mutation route.

## 5. Student: take and submit

1. Sign out, then sign in with the Student credentials from the private file.
2. Open **Join quiz**, enter the assigned code, and review the real preflight.
3. Start the quiz, save each answer, move through the questions, and submit.
4. Confirm that the receipt is present and the score is withheld.

## 6. Professor: release; Student: view

1. Sign in as the Professor, open the quiz, and select **Results**.
2. Release the synthetic Student's scored result.
3. Sign back in as the Student and reopen the submitted attempt.
4. Confirm that only earned and maximum points are visible; answer keys and
   item-level correctness must remain absent.

## 7. Stop and clean up only owned temporary resources

Stop `php artisan serve` with `Ctrl+C`. Clear the process-scoped environment
variables in that terminal if it remains open:

```powershell
Remove-Item Env:DB_SCHEMA -ErrorAction SilentlyContinue
Remove-Item Env:MARKADO_INSTITUTIONAL_EMAIL_DOMAINS -ErrorAction SilentlyContinue
Remove-Item Env:MARKADO_ASSESSMENT_ROUTES_ENABLED -ErrorAction SilentlyContinue
Remove-Item Env:MARKADO_ASSESSMENT_EXPIRATION_ENABLED -ErrorAction SilentlyContinue
```

The guarded cleanup action requires the exact schema twice, verifies the
command-owned marker, refuses `public`, and then removes only that synthetic
schema and its ignored credential file:

```powershell
php artisan markado:demo cleanup --schema=markado_demo_v89_local --confirm=markado_demo_v89_local
```

Do not substitute `public` or a non-demo schema; the command refuses both.
