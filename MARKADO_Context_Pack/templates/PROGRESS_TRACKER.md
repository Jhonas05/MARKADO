# MARKADO Progress Tracker

**Updated:** 22 October 2026. States distinguish interactive UI evidence from
production implementation. A prototype state does not claim Laravel,
PostgreSQL, authentication, authoritative timing, or policy completion.

| ID | Deliverable | State | Evidence | Blockers |
|---|---|---|---|---|
| F01 | Dual timers | UI PROTOTYPE | Display-only whole-quiz countdown in `src/App.jsx`; browser and interaction QA | Per-question timer and D04-D10 policies |
| F02 | Results + exports | UI PROTOTYPE | Local result preview and question breakdown in `src/App.jsx`; no export implementation | PDF/DOCX, D11-D12 |
| F03 | Access code/link | UI PROTOTYPE | Valid/invalid demo-code flow and automated interaction test in `src/App.test.jsx` | Server lookup, auth, D02,D22 |
| F04 | AI draft generator | NOT STARTED | - | D17-D20 |
| F05 | Date/time availability | UI PROTOTYPE | Schedule/timezone presentation in instructions screen | Server enforcement, D07,D10 |
| F06 | Student number + email | NOT STARTED | - | D02,D13-D14 |
| F07 | Randomization | NOT STARTED | - | D03 |
| F08 | Help / Guide | UI PROTOTYPE | Context-sensitive accessible panel and automated interaction test | Content approval and D21 |

Update based on actual repository and QA evidence only.
