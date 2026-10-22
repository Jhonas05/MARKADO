# MARKADO

MARKADO is an online quiz and assessment management system for students,
instructors, and authorized academic administrators. The approved production
direction is Laravel, PostgreSQL, React, and Inertia.js.

This repository currently contains the rapid UI prototype foundation. Prototype
screens use synthetic data only and do not claim server persistence,
authoritative timing, authentication, grading, or production readiness.

## Local development

Requirements: Node.js 22 or newer.

```bash
npm install
npm run dev
```

## Verification

```bash
npm run lint
npm test
npm run build
```

Project requirements and decision records are preserved in
`MARKADO_Context_Pack/`. Items marked `[OPEN]` remain product decisions and are
not silently implemented as academic policy.
