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

The `/` and `/foundation` routes serve a synthetic Student Dashboard through
Laravel and Inertia. `/login` supports provisioned accounts only, and
`/workspace` demonstrates a protected session. There is no public registration
route and no role can be self-assigned. Quiz persistence, resource-scoped
authorization, authoritative timing, grading, AI generation, and result-release
policy remain unimplemented pending approval.
