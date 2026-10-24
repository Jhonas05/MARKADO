# MARKADO

MARKADO is an online quiz and assessment management system for students,
instructors, and authorized academic administrators. The approved production
direction is Laravel, PostgreSQL, React, and Inertia.js.

This repository contains the rapid UI prototype plus an incremental Laravel
application in `server/`. The Phase 5B Professor Quiz Builder persists private
drafts and approved question formats; the standalone prototype screens still
use synthetic data and do not claim authoritative timing, grading, or
production readiness.

## Student prototype

The Phase 1 flow includes:

- Student overview with a ready assessment and synthetic recent results
- Quiz-code lookup using the demo code `BIO-104`
- Pre-start instructions and explicit acknowledgement
- Responsive five-question quiz runner with a display-only timer
- Answer review, submission confirmation, and local result preview
- Answer explanations and a context-sensitive Help & Guide panel

The prototype intentionally labels local answer state and result calculations.
Academic expiry, release, identity, attempt, and outage policies remain open and
are not represented as production behavior.

## Professor prototype

Phase 2 adds a separate Professor workspace while preserving the Student flow.
Use the **Professor prototype** control in the Student header, or open
`/#professor` during local development. The workspace includes:

- Dashboard, quiz management, and a five-step creation workflow
- Manual question editing and a mock AI drafting/review flow
- Separate timer, availability, and randomization controls
- Student preview plus simulated publishing and sharing
- Synthetic submission monitoring and alphabetical/ranked results
- PDF/DOCX export previews and a searchable Professor guide

The standalone Professor prototype remains local to the browser. The authenticated
Laravel workspace at `/professor/quizzes` provides real owner-scoped draft,
MCQ, and True/False authoring. The Laravel assessment flow also implements
server-authoritative attempts, objective scoring, and owner-controlled score
release behind default-off activation gates. AI generation, rankings, exports,
and unresolved privileged actions remain disabled or explicitly simulated.

## Administrator prototype

Phase 3 adds a scoped Administrator workspace using the same MARKADO Ink &
Marker design system. Use the **Administrator prototype** control from the
Student or Professor header, or open `/#admin` during local development. The
workspace includes:

- An operational dashboard with synthetic account and assessment metrics
- Searchable user management with role/status filters and protected details
- Assessment oversight with ownership, schedule, state, and participation data
- Aggregate reporting, accessible chart summaries, date validation, and mock exports
- Context-sensitive administrator help and privacy reminders

Administrator permissions remain an open product decision. Management actions
are confirmation-gated mock previews, and generic administrator views do not
expose learner grades, responses, answer keys, passwords, or unnecessary
contact information.

## Design system

Student, Professor, and Administrator experiences share the Claude-derived
**MARKADO — Ink & Marker** design system in `src/design-system.css`, including
light/dark tokens, Lexend and Atkinson typography, responsive navigation,
visible focus states, and reduced-motion support.

## Local development

Requirements: Node.js 22 or newer.

```bash
npm install
npm run dev
```

## Laravel + Inertia application

The Laravel 12 application under `server/` preserves the verified prototype
while features migrate incrementally. Laravel serves the Student Dashboard at
`/` and the authenticated Professor Quiz Builder at `/professor/quizzes`
through Inertia and React, reusing the Ink & Marker design system.

PostgreSQL is the configured application database in `server/.env.example`, and
credentials remain local and untracked. Fast tests use in-memory SQLite; the
dedicated CI job and documented isolated local schema run the complete suite on
PostgreSQL. See `server/README.md` for setup and ADR-003 for the exact approved
Phase 5B boundaries.

## Verification

```bash
npm run lint
npm test
npm run build

cd server
vendor/bin/pint --test
php artisan test
npm run build
```

Project requirements and decision records are preserved in
`MARKADO_Context_Pack/`. Items marked `[OPEN]` remain product decisions and are
not silently implemented as academic policy.
