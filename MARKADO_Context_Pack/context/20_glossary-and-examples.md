# Glossary, Concrete Examples and Discussion Prompts

## Glossary
- **Quiz:** instructor-authored assessment with policy, questions, schedule and results.
- **Quiz revision:** fixed version of quiz questions and settings used for attempt consistency.
- **Quiz code:** unique join identifier; not proof of user permission.
- **Availability window:** global dates/times when quiz access may be allowed.
- **Attempt:** a student's individual session for a specific quiz/revision.
- **Per-question timer:** time associated with the current individual question.
- **Session timer:** overall attempt duration.
- **Answer save:** accepted persistence of one response, distinct from final submission.
- **Submission:** terminal transition of an attempt for grading/review.
- **Ranked results:** ordered high-to-low score table under a documented tie policy.
- **AI draft:** generated question requiring instructor inspection and approval.
- **Audit event:** durable actor/action/time/resource record for accountability.
- **HCI:** Human-Computer Interaction; interaction quality, clarity and accessibility.
- **Idempotency:** retrying a request does not produce unwanted additional effects.
- **Authoritative clock:** trusted source of time for deadline enforcement when deployment supports one.

## Scenario A - first-time student [REC]
Student receives quiz code `AB7C9K` (fictional example). On join, the system requests login, validates eligibility, displays instructions with open/close schedule and time rules, and starts only after the student explicitly confirms. The code alone never reveals hidden questions or the answer key.

## Scenario B - instructor with AI source [REC]
Professor uploads a course handout, requests five draft questions, reviews source references and edits two ambiguous options, rejects one question, and approves four. The quiz cannot silently publish the rejected or unreviewed item.

## Scenario C - ranking tie [REC]
A class roster has Bea (8/10), Carlo (8/10), Aya (9/10). Descending-score order presents Aya first, then Bea/Carlo under the approved tie display rule. Their academic score tie remains a tie, even if the visible list has distinct row numbers.

## Scenario D - two requests at once [REC]
A student taps Submit as the timer expires. The server handles submission and expiry atomically and records one final state. UI refresh must show the same authoritative result rather than producing two score rows.

## Scenario E - internet connection drops during an online quiz [REC; outage rules OPEN]
MARKADO runs online with Laravel and a central server [DECIDED]. If a learner loses internet access while typing an answer, the interface must not label that answer "Saved" unless Laravel acknowledged persistence. On reconnect, load the authoritative attempt state and original deadlines. Whether any late-save grace policy applies remains D09 [OPEN]; a fully offline-device examination mode is **not** part of the chosen scope.

## Discussion prompts for CHI presentation [REC]
1. How do visible progress and timer feedback reduce uncertainty while respecting accessibility?
2. Why does "student number + email" only reduce duplicates rather than prove identity?
3. How does explicit quiz scheduling help instructors and learners avoid ambiguity?
4. What makes editable AI drafts safer than unattended publication?
5. Why is preserving a student's exact question order important when choices are randomized?
6. How do plain-language errors and Help/Guide affect successful task completion?
7. What does a class result export disclose, and who should be allowed to download it?
8. Which of the open decisions should academic stakeholders approve first?

## Final note
Use this pack to **ask sharper questions and implement approved answers**. It is a development context, not evidence that code, database, security controls or test runs already exist.
