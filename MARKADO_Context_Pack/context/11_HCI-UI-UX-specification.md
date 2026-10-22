# HCI, Information Architecture, Screen Requirements and Accessibility

## HCI principles [REQ + REC]
Source goals are understandable navigation, predictable flows, clear feedback, accessible controls, visible timer, progress awareness, responsive layout, validation and recoverability. Operationalize them with: clear system status, recognition rather than recall, error prevention, user control, consistent language, logical keyboard order and accessible feedback.

## Proposed student screen inventory [REC]
| Screen | Key regions | Primary action | Important empty/error state |
|---|---|---|---|
| Registration | student number, email, privacy explanation, recovery link | Create account | Duplicate/recovery without account enumeration |
| Sign in | identity fields, help, errors | Sign in | Failed sign-in and reset guidance |
| Join quiz | code/link entry, optional login guidance | Find quiz | Not found / unavailable / access denied |
| Rules / pre-start | title, schedule, time rules, attempt policy, question count | Start attempt | Not yet open / no eligibility |
| Quiz runner | prompt, choice/input, timer, progress, save state, help | Save/Next | Disconnected/retry, expired question |
| Review answers | question statuses, unanswered warning | Submit | Late/expired policy |
| Submission receipt | server-confirmed attempt reference, timestamp | Return to quizzes | Pending confirmation retry |
| My results | release-state label, grade/feedback if permitted | View result | Pending/not released |
| Help / Guide | searchable topics and task steps | Open relevant guide | Support contact fallback |

## Proposed instructor screen inventory [REC]
Quiz list with Draft/Published/Closed statuses; step-by-step quiz builder; question editor; AI upload/review workspace; schedule/timer editor; student preview; publish confirmation; share code/link view; attempts monitoring; alphabetical/ranked results; export action and export history where retained.

## Online reliability cues [REC]
- Explicitly show **Connected**, **Saving**, **Saved by server**, **Connection lost**, and **Submission confirmed** as distinct UI states where meaningful.
- When the browser cannot reach Laravel, never display "Saved" merely because an answer was typed. Show a safe retry/reconnect action consistent with the approved outage/late-policy rules.
- After reconnecting, reconcile displayed question state, persisted response and remaining time against the server without extending deadlines.
- Do not offer offline examination as a promised feature.

## React + Inertia.js implementation considerations [DECIDED + REC]
The frontend framework **React**, integrated with Laravel using **Inertia.js**, is approved. Recommended UX contracts:
- Keep timer, question navigator, and save-state components reusable and accessible. The countdown is visual only; deadlines come from the server.
- For an Inertia page visit or redirect after save/submit, avoid accidentally resetting React input state or implying an unacknowledged answer was persisted.
- After refresh/back/reconnect, reconcile UI state against the attempt state returned by Laravel; preserved React form state is not the authoritative record.
- Share only safe, actor-authorized Inertia props, including in globally shared page props; no hidden answer key or unreleased grade.
- Loading, disable, error, retry, and final-receipt states need browser QA, not only component snapshots.

## Interaction details [REC]
- **Before Start:** Include quiz title, number of items, total timer, per-question timer rules, attempt count, open/close timestamp **with timezone**, navigation/auto-submit behavior and when scores will be visible.
- **During Attempt:** Keep clock and progress at stable screen positions. Mark current question, answered/unanswered, saved/saving/error, and help without obscure popups.
- **Before Submit:** Make final submission distinct from "Next"; warn on unanswered items; use a confirmation that does not accidentally extend time.
- **After Submit:** Show a receipt based on a server-accepted transition, never merely a local button click.

## Accessible interaction targets [REC]
Aim to assess against **WCAG 2.2 Level AA** where applicable (external recommendation, not a statement of certified conformance). Ensure keyboard access, visible focus, meaningful labels, contrast, adequate targets, form validation descriptions, correct heading order, reduced-motion support and reasonable screen-reader timer announcements. Offer time-related accommodations only once a formal policy exists.

## Responsive behavior [REC]
Desktop: stable timer header, main question area, navigable question map. Mobile: simplified question layout, persistent accessible time signal, reachable primary actions and no horizontally clipped answer content. Do not rely solely on hover, color or desktop-only shortcuts.

## Design tokens suggestion [REC]
Choose a restrained palette, strong text/background contrast, 8-point spacing rhythm, consistent corner radius, and semantic color meaning (information, warning, success, error). Actual MARKADO brand identity is [OPEN]; do not invent official colors/logo. Visual consistency must serve usability more than decoration.

## Microcopy examples [REC]
| Context | Suggested text |
|---|---|
| Quiz unavailable | "This quiz isn't available right now. Check its opening and closing schedule." |
| Timer running | "Time remaining: 12 minutes, 30 seconds." |
| Save pending | "Saving your answer..." |
| Save failed | "Your answer wasn't saved. Try again. Your timer is still running." |
| Submit confirmation | "Submit your final answers? You may not be able to change them afterward." |
| Score withheld | "Your result isn't available yet. Your instructor will release it according to the quiz rules." |
| AI review | "AI-generated question - check the source, answer, and wording before approving." |

## Research and evaluation methods [REC]
A small moderated usability test with students and instructors; task completion and error observation; accessibility audit; mobile/keyboard testing; brief post-task ease survey. Test under realistic time pressure but avoid intentionally stressful or coercive conditions. Document findings and changes before treating HCI goals as achieved.
