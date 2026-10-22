# ADR-002: PostgreSQL Database and React (Inertia.js) Frontend

**Status:** DECIDED by user  
**Date:** 22 October 2026  
**Decision scope:** D26 database engine and D27 frontend architecture. No implementation is claimed.

## Context
The original MARKADO documentation left database and UI integration open. After confirming an **online web application with Laravel**, the user selected **"PostgreSQL"** and **"React (Inertia.js)"**.

## Approved decisions [DECIDED]
1. **PostgreSQL** is MARKADO's relational database engine. Data for users, quizzes, revisions, attempts, answers and grades will be centrally stored and validated on the server.
2. **React**, integrated through **Inertia.js**, is the frontend technology for the Laravel web application. Laravel remains the routing, authentication, authorization and business logic authority.
3. Keep the established **online** deployment and **Laravel (PHP)** backend decisions from ADR-001.

## Design implications [REC]
- Use Laravel migrations, PostgreSQL database constraints and transaction-safe attempt finalization. Decide canonical student-number/email uniqueness scope before committing indexes.
- Show timer, question progress and save feedback in React. Enforce deadlines and scoring on Laravel from authoritative persisted PostgreSQL state.
- Authenticate and authorize each Inertia page/action server-side; ensure Inertia props do not include unpublished answer keys, unreleased results or cross-user data.
- Handle refresh, history navigation, duplicate submissions and network errors as visible states. A submitted receipt must come from the server rather than an optimistic React-only transition.
- Prefer modular Laravel boundaries initially, but keep detailed architecture open to repo inspection and later implementation design.

## Not selected by this ADR [OPEN]
- Where to host Laravel/PostgreSQL, managed/self-hosted database, backup/restore targets, file storage and job queues (D28).
- Exact Laravel/PHP, React and Inertia versions or upgrade path (D29); dependency compatibility must be verified.
- TypeScript vs JavaScript, Tailwind/UI library, server-side rendering, testing library, auth starter kit and other supporting packages.
- Academic assessment policy, student identity scope, allowed attempts, timer precedence, question format, result release, AI provider and outage rules.

## Verification criteria [REC]
- Repository actually uses Laravel + PostgreSQL + React/Inertia.js and has reproducible migrations/config (once implemented).
- Security tests prove authorization and absence of sensitive Inertia props.
- PostgreSQL-backed concurrency tests prove exactly-once submission, immutable snapshots and uniqueness behavior.
- Browser QA validates question navigation, save acknowledgment, refresh/reconnect and deadlines.

**Revisit** only on new explicit user decision or a blocking dependency/architecture issue backed by evidence and approved change record.
