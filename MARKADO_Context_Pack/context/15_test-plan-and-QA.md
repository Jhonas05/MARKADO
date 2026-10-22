# Testing, Manual QA, Edge Cases and Acceptance Gates

## Testing layers [REC]
**Unit:** scoring, permutations, normalization, timer/deadline calculations, state transitions. **Integration:** database uniqueness, transactions, data access and export formatting. **Contract:** permission decisions and error types. **End-to-end:** author-publish-student-attempt-results-export. **Usability/accessibility:** student comprehension, keyboard/mobile, timer clarity. **Security:** cross-user access, input validation, files, sessions and grade leakage.

## Test data strategy [REC]
Use synthetic learner identities and grades; do not import real student data into development environments. Test names with Unicode/accent, similar names, duplicate student numbers, common email case variants, long instruction text, large rosters, quizzes with tie scores, attempts close to deadlines and malformed uploaded material.

## Framework-aware verification [REC]
Laravel-specific recommendations: HTTP feature tests for every protected action, policy tests for cross-student/instructor access, database transaction/invariant tests, fake-clock tests for timing boundaries, queue/job retry tests, and deployment smoke tests over HTTPS. With React + Inertia.js selected, add component/browser and end-to-end navigation/refresh/save-feedback tests; with PostgreSQL selected, run constraint, transaction and indexing tests against PostgreSQL, not SQLite-only substitutes.

## Automation suites [REC]
- Property-based randomization and stable answer-ID mapping.
- Time boundaries using fake authoritative clock.
- Concurrent registration with same student number/email.
- Duplicate start and submit, including simulated timeout/retries.
- Score finalization under simultaneous expiry.
- Role/ownership authorization for every endpoint.
- PDF/DOCX export equivalence to on-screen report model.
- Snapshot immutability during a published quiz revision.
- Generated question approval gating.
- Mobile/keyboard accessibility checks where feasible.

## Sample test matrix
| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-001 | Registration | Sign up with valid number + email | Account flow proceeds under chosen verification policy |
| QA-002 | Registration | Repeat existing student number | Uniqueness conflict safely handled |
| QA-003 | Registration | Repeat existing email | Uniqueness conflict safely handled |
| QA-004 | Registration | Two simultaneous registrations same number | Exactly one persistent student identity |
| QA-005 | Registration | Recover mistyped or changed email | Authorized recovery without grade misassignment |
| QA-006 | Join | Enter valid code while eligible | Instructions shown, no unapproved attempt start |
| QA-007 | Join | Invalid code | Safe, usable error |
| QA-008 | Join | Unpublished quiz code | No attempt created/no answers leaked |
| QA-009 | Join | Other instructor's private quiz | Denied despite valid link |
| QA-010 | Join | Unauthorized student | Access denied |
| QA-011 | Availability | Open boundary - 1 millisecond | Not open under defined rule |
| QA-012 | Availability | Exactly at open boundary | Matches documented rule |
| QA-013 | Availability | Exactly at close boundary | Matches documented rule |
| QA-014 | Availability | Timezone conversion | Same instant enforced and displayed clearly |
| QA-015 | Timer | Browser reload | Deadline unchanged |
| QA-016 | Timer | Manual device clock adjustment | Cannot grant more time in trusted-server mode |
| QA-017 | Timer | Question changes | No unapproved timer reset |
| QA-018 | Timer | Whole quiz timer expires | Approved expiry outcome, once |
| QA-019 | Timer | Both timers collide | Policy deterministic and documented |
| QA-020 | Timer | One clock expires during request | Server rule consistent |
| QA-021 | Attempt | Double-click Start | No unintended extra attempt |
| QA-022 | Attempt | Two tabs answering | Single coherent server state |
| QA-023 | Attempt | Refresh during progress | Valid saved answers present |
| QA-024 | Attempt | Drop network before save acknowledgement | No false "saved" UI assertion |
| QA-025 | Attempt | Repeat same answer save | Idempotent or documented coherent behavior |
| QA-026 | Attempt | Submit twice | One terminal submission/receipt |
| QA-027 | Attempt | Submission vs expiry race | Exactly one valid final state |
| QA-028 | Attempt | Submit with unanswered items | Warning and approved behavior |
| QA-029 | Attempt | Save after terminal submit | Modification rejected |
| QA-030 | Question | Shuffle question order | All items included exactly once |
| QA-031 | Question | Shuffle choices | Correct answer preserved via choice ID |
| QA-032 | Question | Wrong/missing answer key | Publish blocked |
| QA-033 | Question | Same option text twice | Validation and intended scoring preserved |
| QA-034 | Question | Long prompt / media | No clipping or inaccessible control |
| QA-035 | AI | Upload allowed material | Job created under owner scope |
| QA-036 | AI | Upload disallowed file type | Safe rejection |
| QA-037 | AI | Generation yields ambiguous question | Reviewer can reject/edit |
| QA-038 | AI | Unapproved draft present | Cannot publish it silently |
| QA-039 | AI | Malicious instructions in source text | Not treated as system instruction |
| QA-040 | AI | Low extraction quality | Clear warning, no fabricated source claim |
| QA-041 | Quiz | Publish with no questions | Blocked with guidance |
| QA-042 | Quiz | Edit after active attempts | Started attempt snapshot unchanged |
| QA-043 | Quiz | Published quiz with invalid schedule | Blocked / policy error |
| QA-044 | Results | Sort alphabetically | Stable expected name order |
| QA-045 | Results | Sort high to low | Correct comparable scoring order |
| QA-046 | Results | Tie scores | Consistent tie handling and stable order |
| QA-047 | Results | Another student's result request | Access denied |
| QA-048 | Results | Result not released | Student sees safe withheld state |
| QA-049 | Reports | Export PDF | Current authorized filter + sort preserved |
| QA-050 | Reports | Export DOCX | Same rows, score values, ordering as PDF |
| QA-051 | Reports | Large class and multi-page PDF | Table header repeat and readable layout |
| QA-052 | Reports | Unauthorized export endpoint | Denied without file disclosure |
| QA-053 | HCI | Keyboard-only quiz completion | All supported actions reachable |
| QA-054 | HCI | Mobile viewport | Timers/questions/actions readable |
| QA-055 | HCI | Help during timed attempt | Help reachable; timer behavior honest |
| QA-056 | HCI | Error or disconnected response | User understands next action |
| QA-057 | Security | Guess resource ID across instructors | Denied |
| QA-058 | Security | Submit malformed choice/question ID | Rejected, no score corruption |
| QA-059 | Security | Inject markup in question text | Rendered safely |
| QA-060 | Operations | Restore simulated backup | Consistent users/quizzes/attempts recovered |

## Online Laravel-specific QA additions [REC]

| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-061 | Connectivity | Disconnect while typing an answer but before server save acknowledgement | Browser never claims unsaved content is saved; clear recovery action appears |
| QA-062 | Connectivity | Reconnect after browser refresh or temporary internet interruption | Laravel returns authoritative answers/state; original persisted deadlines are unchanged |
| QA-063 | Laravel authorization | Student or professor accesses a foreign attempt/quiz via direct URL/HTTP request | Server denies using policy/resource scope; no protected result or answer leaked |
| QA-064 | Laravel validation | Client submits forged score or browser-adjusted remaining time | Server ignores untrusted grade/time and rejects invalid request values |
| QA-065 | Laravel concurrency | Two web requests submit/expire the same attempt simultaneously | Exactly one final attempt transition and grade receipt are persisted |
| QA-066 | Queues and exports | Export job retries and unrelated user guesses generated file URL | No duplicate report disclosure; only authorized requesters can obtain the artifact |
| QA-067 | Expiry safety | Laravel scheduled expiry worker runs late or is temporarily offline | Server rejects late answers/submits under policy even before delayed sweeper catches up |
| QA-068 | Online deployment | Verify HTTPS sessions, CSRF protection, production error handling and secret exposure | Unauthorized browser requests blocked; secure cookies; no credentials or debug trace disclosed |

## PostgreSQL + React/Inertia.js QA additions [REC]

| ID | Area | Steps/scenario | Expected result |
|---|---|---|---|
| QA-069 | PostgreSQL registration | Parallel requests try to register the same canonical student number or email | Only one account succeeds under the approved uniqueness scope; no duplicate survives |
| QA-070 | PostgreSQL atomic submit | Submit and expiry race across separate Laravel workers and PostgreSQL connections | Exactly one terminal state/grade receipt and no partial transaction |
| QA-071 | PostgreSQL time | Student and instructor browsers use different local timezones | Stored deadline remains same instant and authorized boundaries still enforced |
| QA-072 | Inertia security | Inspect initial and subsequent Inertia page props, shared props and browser history | No answer key or another student's unreleased result is exposed |
| QA-073 | React save status | Disconnect during answer save, then navigate back or refresh | UI does not claim unacknowledged answer saved; server state is recovered safely |
| QA-074 | React countdown | Change browser clock and refresh timed attempt screen | Visual timer re-derives from fixed server deadline; acceptance of late answer denied |
| QA-075 | Inertia form submission | Double-click Submit and retry with a browser refresh or history navigation | One authoritative attempt submission receipt; no duplicate grade |
| QA-076 | Inertia navigation | Navigate among questions with browser back/forward or Inertia visits | Answer UI rehydrates correctly against server state; random order/deadline fixed |

## Manual QA protocol [REC]
For each role, execute happy path then exception path. Capture tester, date/build, feature ID, preconditions, expected outcome, actual outcome, severity, screenshot/trace location and resolution. Every high-risk problem gets a regression test before closure.

## Exit criteria [REC]
- F01-F08 each mapped to passing tests and product approval.
- Zero known critical breaches of identity, authorization, timing, submission or grade correctness.
- Confirmed decisions documented for all [OPEN] items blocking build.
- PDF/DOCX output visually checked, not only string-compared.
- Accessibility/usability test outcomes recorded with follow-ups.
- Backup/recovery and incident-response rehearsal complete for production deployment.
