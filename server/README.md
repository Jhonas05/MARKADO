# MARKADO Laravel foundation

This directory is the incremental Laravel + React/Inertia foundation. The
original Vite prototype remains runnable from the repository root while screens
are migrated behind server-owned routes.

## Local setup

Requirements: PHP 8.2+, Composer 2, Node.js 22.12+, npm, and PostgreSQL for a
real database connection. On this Windows workstation, the active CLI is
`C:\xampp\php\php.exe` and its loaded configuration is
`C:\xampp\php\php.ini`.

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
php artisan serve
npm run dev
```

Set the `DB_*` variables in the untracked `.env` file for a local PostgreSQL
instance. Never commit credentials. The regular local test suite uses in-memory
SQLite; the dedicated CI job runs the complete suite against PostgreSQL.

For a local PostgreSQL test run without touching development tables, create a
schema owned by `markado_app` (for example, `markado_test`) and run the suite with
`DB_CONNECTION=pgsql` and `DB_SCHEMA=markado_test`. `config/database.php` defaults
to the normal `public` schema when `DB_SCHEMA` is not set. Never point a
`RefreshDatabase` test run at a schema containing development data.

## Windows PostgreSQL setup

Confirm that PHP can load the driver before installing a server:

```powershell
php --ini
php -m | Select-String -Pattern 'pdo_pgsql|pgsql'
php -r "echo implode(',', PDO::getAvailableDrivers()), PHP_EOL;"
```

If `pdo_pgsql` and `pgsql` are absent but the matching DLLs exist under the
active PHP `ext` directory, enable these lines in the loaded `php.ini`, then
open a new terminal:

```ini
extension=pdo_pgsql
extension=pgsql
```

Installing the PostgreSQL Windows service is a machine-level change and must be
approved first. After approval, the current development reference can be
installed interactively so its administrative credential is never placed in a
command or log:

```powershell
winget install --id PostgreSQL.PostgreSQL.17 --exact --source winget --interactive
```

PostgreSQL 17 is a development/CI reference, not an approval of a production
service or version. In an administrator `psql` session, create a non-superuser
that owns only the MARKADO development database. The `\password` command asks
interactively and avoids storing the value in shell history:

```sql
CREATE ROLE markado_app LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
CREATE DATABASE markado OWNER markado_app;
REVOKE ALL ON DATABASE markado FROM PUBLIC;
GRANT CONNECT, TEMPORARY ON DATABASE markado TO markado_app;
\password markado_app
```

Copy `.env.example` to the ignored `.env`, fill in the local `DB_*` values, and
leave the credential only in that untracked file. Verify the exact layers:

```powershell
php artisan config:clear
php artisan tinker --execute="dump(DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME));"
php artisan migrate:status
php artisan migrate
```

The PDO command must report `pgsql`; migration output alone must not be treated
as proof of the active driver.

## Safe development reset

First inspect the environment and connected database. `migrate:fresh` drops all
tables and is only for a disposable local database:

```powershell
php artisan about --only=environment
php artisan db:show
php artisan migrate:fresh
```

Never run the reset command against a shared, staging, or production database.

## Verification

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

The `/` route is the production-style public entry and `/foundation` retains the
synthetic Student Dashboard as a migration fixture. `/login` accepts provisioned,
active accounts only. The server-stored role sends students to
`/student/dashboard`, instructors to `/professor/quizzes`, and academic
administrators to `/admin/dashboard`; changing a client URL or form label cannot
change that role. Public student registration and recovery have truthful design
surfaces but no mutation routes while D13/D14 remain unresolved. Staff
registration and privileged administrator actions remain disabled.

Provisioned instructors can use `/professor/quizzes` for PostgreSQL-backed,
owner-scoped draft authoring with single-answer MCQ and True/False questions.
Authenticated students can open the rate-limited `/student/quizzes/join`
preflight; draft, unknown, and unauthorized codes do not disclose quiz details.
When explicitly enabled, eligible students can start or resume one assessment,
save acknowledged answers, move forward through questions, and finalize at the
earliest authoritative deadline. Owning instructors can view scored attempts
and manually release score/total. Audit events contain identifiers and outcomes
but no code, question, choice, or answer content. AI generation, rankings,
exports, regrading, and unresolved privileged actions remain unavailable.

## Internal assessment-engine foundation

The route-gated engine now has database-backed services for one-attempt
creation/resume, immutable question snapshots, stable seeded permutations,
server-authoritative session and question deadlines, versioned idempotent answer
saves, and atomic terminal receipts. The implementation preserves the approved
hard-close cap and never derives time from the browser.

ADR-007 records the approved final-question, objective-scoring, and manual
score/total release rules. The HTTP routes and expiration worker remain off by
default while D13/D14 identity and recovery, D15/D23 privileged operations, and
the remaining exception policies are unresolved. The additive assessment
migration can be previewed with `php artisan migrate --pretend`. Run
PostgreSQL-backed tests only against a dedicated disposable test schema, never
the local development schema.

To activate a reviewed environment, set
`MARKADO_ASSESSMENT_ROUTES_ENABLED=true`. Automatic expiration additionally
requires `MARKADO_ASSESSMENT_EXPIRATION_ENABLED=true` and one supported Laravel
scheduler runner (for example, `php artisan schedule:work` locally or a single
once-per-minute `php artisan schedule:run` production trigger). Request-time
deadline reconciliation stays authoritative if a worker is delayed; do not
enable these flags before migrations, provisioned identities, monitoring, and
the applicable policy approvals are verified.
