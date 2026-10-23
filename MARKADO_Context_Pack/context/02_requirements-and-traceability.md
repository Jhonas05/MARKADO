# Feature Requirements, Acceptance Criteria and Traceability

The eight features below are **requested**. Their detailed implementation criteria are **recommended interpretations** of the supplied PDF and should be confirmed against project policy.

## F01 - Time limits
- **F01.1 [REQ]** Instructor/admin configures a limit per question and for the quiz session.
- **F01.2 [REQ]** Student sees remaining time clearly.
- **F01.3 [REC]** The authoritative attempt timer is computed by the trusted assessment runtime, never from client display time alone.
- **F01.4 [REC]** Reload, second tab, clock adjustment, or reconnection cannot grant an extension.
- **F01.5 [DECIDED]** Both timers may run simultaneously. The earliest persisted server deadline wins; question expiry locks/preserves/advances without backward navigation, and whole-session or global expiry finalizes atomically. The final-question per-question-expiry transition remains open.
- **Accept:** A cross-device refresh preserves deadlines; expired answer mutations are rejected; UI announces time warnings accessibly.

## F02 - Result listing, ranking and export
- **F02.1 [REQ]** Alphabetical list by student name.
- **F02.2 [REQ]** Alternative highest-to-lowest score order.
- **F02.3 [REQ]** Exports to PDF and Word.
- **F02.4 [REC]** Tie breaks and name normalization are deterministic, with stable reference IDs.
- **F02.5 [REC]** Both export formats use the same authorized query, labels, filters and result-release policy.
- **F02.6 [OPEN]** Score basis, handling of multiple attempts, grade adjustments and ties.
- **Accept:** The exported roster reconciles with the selected on-screen roster, including statuses and ties.

## F03 - Quiz access code and link
- **F03.1 [REQ]** Every published quiz has a unique access code and join link.
- **F03.2 [REQ]** Code/link entry feels straightforward like a quiz-access flow used in popular learning products; no third-party dependency is implied.
- **F03.3 [REC]** Access token is non-guessable enough for its threat model, and lookup is rate-limited.
- **F03.4 [REC]** Enforce authentication, publication status, availability and eligibility **after** link resolution.
- **Accept:** Wrong, expired, unpublished, unauthorized and malformed access attempts fail consistently with safe feedback.

## F04 - Question generation from learning materials
- **F04.1 [REQ]** Instructor/admin provides source materials for question generation.
- **F04.2 [REQ]** Generated questions are drafts requiring human approval before publishing.
- **F04.3 [REC]** Preserve source provenance and flag low-confidence output, ambiguous answers and missing evidence.
- **F04.4 [OPEN]** Supported file types, OCR, provider, retention of uploads, token/cost limits, allowed languages.
- **Accept:** Rejected draft never appears in a student attempt; each accepted draft is editable and attributable to its review.

## F05 - Quiz availability by date and time
- **F05.1 [REQ]** Configure opening and closing date/time.
- **F05.2 [REC]** Store times unambiguously and show effective timezone in authoring and student views.
- **F05.3 [DECIDED]** Opening is inclusive and closing is exclusive for new starts. An active attempt is capped and atomically finalized at the global close under ADR-005.
- **Accept:** Boundary tests demonstrate exactly when starting is accepted or denied.

## F06 - Student number and email registration
- **F06.1 [REQ]** Require both student number and email to register.
- **F06.2 [REQ]** Reject duplicates using the chosen institutional uniqueness scope.
- **F06.3 [REC]** Add transactional uniqueness constraints; normalize appropriately; provide a safe recovery path.
- **F06.4 [OPEN]** Who issues/validates a student number; verification requirement; accounts shared across institutions or school years.
- **Accept:** Two near-simultaneous signups with one student number cannot both succeed; recovery does not reveal an account unnecessarily.

## F07 - Randomization
- **F07.1 [REQ]** Randomize order of questions, choices, or both when enabled.
- **F07.2 [REC]** Persist per-attempt permutation/snapshot so scoring and review refer to stable IDs.
- **F07.3 [REC]** Do not shuffle special ordered-question types unless their semantics support it.
- **F07.4 [OPEN]** Randomization scope and instructor exceptions.
- **Accept:** Scoring stays identical under every permutation and the student's actual displayed order can be reconstructed.

## F08 - Help and Guide
- **F08.1 [REQ]** Provide user help/instructions.
- **F08.2 [REC]** Context-sensitive FAQ, first-time onboarding, keyboard navigation and actionable empty/error states.
- **F08.3 [REC]** A help overlay must not pause or unintentionally terminate an active attempt unless approved by policy.
- **Accept:** A student can find time rules and submission steps before entering a timed attempt and open help safely mid-attempt.

## Cross-feature dependencies
| Feature combination | Required design conversation |
|---|---|
| F01 + F05 | Effective attempt deadline vs global quiz closing time |
| F01 + F08 | Timer visibility and whether help consumes attempt time |
| F03 + F06 | Code/link does not override login or identity checks |
| F04 + F07 | Generated question IDs and stable choice IDs for randomization |
| F02 + F07 | Correct score independent of randomized presentation |
| F02 + F06 | Exports identify the right student without unnecessary data exposure |
| F02 + F05 | Late/expired/in-progress records and results release rules |

## Requirement change protocol [REC]
Record original text, source, proposed interpretation, affected roles, data migration risk, QA impact and approver before changing the baseline. Maintain an ID-based requirements-to-tests matrix (`templates/requirements_traceability.csv`).
