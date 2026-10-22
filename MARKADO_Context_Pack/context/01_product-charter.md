# Product Charter, Scope and Success Criteria

## Problem statement [REQ]
Creating quizzes, handing out access details, controlling time, checking student identities, reviewing results, and exporting class reports can require disconnected manual steps. MARKADO proposes a single controlled flow for these tasks with straightforward student interaction.

## Product outcomes [REC]
1. Instructors can create, review, schedule and publish a quiz without needing technical assistance.
2. Students can reliably register, join, understand the rules, complete an attempt, and confirm submission.
3. Academic users can interpret results without ambiguity about ranking, attempt status or release policy.
4. Administrators can support users without unnecessarily obtaining student answers or grades.
5. As an online web application, the product gives truthful connection/save status during time pressure, errors and temporary internet interruptions, subject to an explicit reconnection/outage policy [OPEN].

## Approved platform direction [DECIDED]
MARKADO will be an **online Laravel (PHP) web application** with **PostgreSQL** as its relational database and **React + Inertia.js** for the web UI [DECIDED]. It is not LAN-only or fully offline per device. Users access the centralized application from their browsers. Hosting provider, exact Laravel/PHP and frontend package versions, auth library, queues, file storage, and backup goals are [OPEN].

## Primary audience
| Persona | Needs | Primary risk |
|---|---|---|
| Student | Clear instructions, accurate timer, safe progress, confident submission | Time loss, mistaken submission, login/duplicate confusion |
| Instructor/professor | Efficient quiz construction, predictable scheduling, trusted results | Wrong answer key, wrong access window, inconsistent scoring |
| Academic administrator | Account and assessment oversight with auditable powers | Excessive access, impersonation, unclear support actions |

## In-scope [REQ]
F01-F08 as indexed in `00_START_HERE.md`; role-limited access; integrity-conscious result processing; HCI guidance including understandable errors and Help/Guide.

## Proposals, not baseline commitments [REC]
Question bank; quiz preview; publishing checklist; auto-save; section/course assignment; audit dashboard; version snapshots; bulk student import; anomaly flags; advanced analytics; manual grading; accommodations; course-based dashboards; activity notifications. Each requires explicit prioritization before implementation.

## Explicitly unspecified [OPEN]
Precise supported question formats, live supervision/proctoring, institution/tenant hierarchy, gradebook integrations, instructor approval levels, provider for AI generation, paid services, question-import limits, data-retention term, device compatibility, Laravel/PHP and React/Inertia versions, supplementary UI packages and production hosting.

## Non-goals unless later authorized [REC]
Do not assume a full LMS, transcript authority, school master records, fee collection, remote proctoring/surveillance, biometric identification, or guaranteed cheating detection. A quiz link is a navigation aid, not sufficient proof of eligibility.

## Candidate outcome measures [REC]
| Measure | Practical definition | Measurement method |
|---|---|---|
| Quiz creation completion | Share of authoring sessions that reach an approved publish step | Creation funnel telemetry without exposing answer content |
| First-try join rate | Eligible students joining a published quiz on first valid attempt | Aggregate join outcome events |
| Submission reliability | Accepted submission acknowledgements / valid submit requests | Server events and retry reconciliation |
| Export consistency | Match between filtered screen results and generated file | Deterministic automated test |
| Help effectiveness | Reported ability to complete common tasks without staff intervention | Usability testing and feedback |

## Stakeholders and sign-off [REC]
Identify faculty lead, student tester, product owner, developer lead, privacy/security reviewer and academic policy decision-maker. Record every blocking [OPEN] in the decisions register with an owner. An attractive UI does not replace consent, correctness, usability testing, or grade integrity.
