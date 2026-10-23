# Phase 4 product-owner decision sheet

This sheet summarizes unresolved MARKADO decisions. It does not approve a
policy. Record the chosen option, approver, and date in
`MARKADO_Context_Pack/context/18_open-decisions.md` before implementing a rule.

D07 was approved on 23 October 2026 in ADR-005. D04-D06 and D08-D10 were then
approved within ADR-006's exact boundaries. This sheet now retains only the
explicit residual edges and unrelated decisions.

## Blocking before the quiz engine

| Priority | Decision IDs | Product-owner question | Why it blocks implementation |
|---|---|---|---|
| 1 | D02, D13, D14 | Is MARKADO scoped to one institution or many, who issues/verifies student numbers, and how are email duplicates recovered? | Defines account boundaries, uniqueness constraints, registration, and recovery. |
| 2 | D03 | Which question types are allowed in V1? | Defines the question/answer schema, editor, validation, and grading paths. |
| 3 | D05 edge | What terminal transition occurs when the final per-question timer expires and no next question exists? | Prevents invented extra time or an unapproved early finalization rule. |
| 4 | D08-D09 exceptions | Who may grant a reset, accommodation, or institution-wide outage remedy, under what limits and audit controls? | Defines exceptional recovery without weakening ordinary one-attempt/no-grace integrity. |
| 6 | D11, D12 | When are results released, what feedback is visible, and how are ties/repeats ranked? | Prevents premature grade or answer-key disclosure and defines reports. |
| 7 | D15, D22, D23 | What may instructors and administrators view/change, which students are eligible, and who can regrade? | Defines authorization policies and audit obligations. |

## Needed before production or external integrations

| Decision IDs | Question | Can foundation work continue? |
|---|---|---|
| D28 | Select hosting, managed PostgreSQL placement, queue/cache, private storage, monitoring, backups, and recovery targets. | Yes for local foundation work; required before a production deployment. |
| D17, D18, D20 | Set upload limits, AI provider/data permissions, and material ownership rules. | Yes for non-AI quiz foundations; required before any real upload or AI processing. |
| D19, D21 | Set retention/export expiry and supported browser/accessibility targets. | Yes for prototypes; required before production release criteria are final. |

## D29 compatibility proposal — not approved

- **Laravel 12.70.x with PHP 8.2.x** for the current workstation. Laravel 12
  declares PHP `^8.2`; Laravel 13.38 declares PHP `^8.3`, which is incompatible
  with the installed PHP 8.2.12 runtime.
- **Inertia Laravel 3.5.x**, which declares PHP `^8.2` and Laravel
  `^11.35|^12|^13`.
- **React 19.3.x and `@inertiajs/react` 3.9.x**; the adapter declares React and
  React DOM `^19` peers.
- **Vite 8.3.x and Laravel Vite Plugin 3.2.x** on Node 22.22.x; both declare a
  compatible Node range and the Vite plugin declares Vite `^8`.

Compatibility was checked against installed runtime versions and current
Composer/npm package metadata on 22 October 2026. Product/technical approval is
still required before D29 is marked decided.
