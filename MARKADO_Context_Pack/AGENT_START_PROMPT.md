# MARKADO - AI Development Agent Bootstrap Prompt

You are working on the MARKADO digital quiz and assessment system. Before proposing or changing code:

1. Read `context/00_START_HERE.md`, `context/02_requirements-and-traceability.md`, `context/13_architecture-and-interfaces.md`, `context/18_open-decisions.md`, `decisions/ADR-001-online-laravel.md`, `decisions/ADR-002-postgresql-react-inertia.md` and all relevant domain chapters.
2. Treat [REQ] as requested scope, [DECIDED] as user-confirmed architecture, [REC] as proposed guidance, and [OPEN] as undecided policy. **Online deployment + Laravel + PostgreSQL + React/Inertia.js are approved; not yet implemented.** Do not substitute another database or frontend pattern, nor choose hosting, exact versions, optional libraries or academic exam policies without approval/repository inspection.
3. Inspect the actual repository and current architecture if available. Do not invent existing features, libraries, migrations, tests or completion status.
4. Give a traceable implementation plan that names F-IDs, decisions, role/authorization boundaries, schema impact, failure modes and QA case IDs.
5. If an unresolved choice blocks correctness (notably timer expiry, result release, attempts, identity scope, database hosting, version compatibility or grading), flag it before implementing that behavior. You may implement policy-neutral foundations safely.
6. Protect student data and instructor answer keys; do not let quiz code alone confer authorization. Preserve audit history, submission integrity and reproducible question/choice order.
7. AI-generated questions are editable drafts requiring human approval. Online Laravel app actions must use server-authoritative clocks/validation, with permission checks and transaction-safe attempt finalization.
8. Verify using Laravel HTTP/policy tests, PostgreSQL constraint/concurrency checks, React/Inertia.js browser interaction tests and manual QA evidence; update `templates/PROGRESS_TRACKER.md` only when evidence exists.

Deliver: scope analysis, decision blockers, implementation steps, test plan, expected risk, and concise completion report grounded in actual tools/results.
