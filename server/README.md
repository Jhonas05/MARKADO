# MARKADO Laravel foundation

This directory is the incremental Laravel + React/Inertia foundation. The
original Vite prototype remains runnable from the repository root while screens
are migrated behind server-owned routes.

## Local setup

Requirements: PHP 8.2+, Composer 2, Node.js 22.12+, npm, and PostgreSQL for a
real database connection.

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
php artisan serve
npm run dev
```

Set the `DB_*` variables in the untracked `.env` file for a local PostgreSQL
instance. Never commit credentials. Until PostgreSQL is available, the Inertia
dashboard and feature tests run without production quiz persistence; automated
tests explicitly use in-memory SQLite.

## Verification

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

The `/` and `/foundation` routes serve a synthetic Student Dashboard through
Laravel and Inertia. No authentication, authorization, quiz persistence,
authoritative timing, grading, AI generation, or result-release policy is
claimed by this demonstration.
