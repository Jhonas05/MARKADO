# MARKADO

MARKADO is an online quiz and assessment management system for students,
instructors, and authorized academic administrators. The approved production
direction is Laravel, PostgreSQL, React, and Inertia.js.

This repository currently contains the rapid UI prototype foundation. Prototype
screens use synthetic data only and do not claim server persistence,
authoritative timing, authentication, grading, or production readiness.

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

Every professor action is local to the browser. AI generation, publishing,
monitoring, grading, and exports are explicitly simulated. Academic policies
that remain open in the Context Pack are labeled rather than silently chosen.

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

## Verification

```bash
npm run lint
npm test
npm run build
```

Project requirements and decision records are preserved in
`MARKADO_Context_Pack/`. Items marked `[OPEN]` remain product decisions and are
not silently implemented as academic policy.
