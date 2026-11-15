# MARKADO browser QA report

## 15 November 2026: partial local and public staging checks

Real Chrome controlled by Playwright was used with synthetic data against a
guarded loopback PostgreSQL demo. It exercised Student signup, local mail
capture and verification, automatic eligible activation, login, unassigned
quiz denial, Professor quiz creation and assignment, Student answer save and
submission, score withholding, Professor release, and released Student score.
Professor PDF and DOCX exports were downloaded; the PDF parsed and the DOCX
archive and document XML validated. A synthetic Administrator could open the
read-only database-backed dashboard, while the Professor route returned 403.
Student and Professor layouts were checked at 1440, 768, and 390 px. The
Administrator layout was checked at those widths and 320 px; a compressed
mobile logo and wrapped sign-out control were corrected. These are selected
checks, not completion of the full accessibility and workflow matrix below.

The public staging health endpoint and login returned HTTP 200. The staging
login was rendered at 1440, 768, and 390 px without horizontal overflow, and
the registration page showed the gated onboarding state. After the Railway CLI
was connected, real Chrome exercised synthetic Professor, Student, and
Administrator login; MCQ/True-False creation; unassigned denial; publication
and assignment; answer-save/refresh; submission; withheld and released scores;
concurrent submission retries; role denial; and PDF/DOCX downloads. Both export
formats parsed successfully. The released result survived a staging app
restart. Selected authenticated pages at 1440, 768, and 390 px showed no
horizontal overflow. A staging-only worker finalized a separate unattended
synthetic attempt, confirmed on the read-only Professor results screen before
the Student reopened it. The synthetic Student was pre-provisioned; real email
delivery and hosted Student self-registration were not verified. The complete
accessibility, theme, and failure-state matrix below remains incomplete.

## Earlier status: 12 November 2026

Status at that time: **BROWSER QA NOT VERIFIED**.

The approved in-app browser runtime was initialized against
`https://markado-app-staging.up.railway.app`, but returned
`No browser is available`. The runtime browser list was empty after the
documented troubleshooting check. No screenshots were captured and no browser
scenario is reported as passed. API/HTTP flow evidence is useful but is not a
substitute for rendered interaction, responsive, keyboard, contrast, console,
or asset verification.

Separately, an authenticated HTTPS smoke run against staging deployment
`810d7197-ee9c-4f83-be54-c1dcc791f198` passed Professor/Student login and
logout, quiz publish/assignment, Student save/advance/submit, repeat-submit
idempotence, withheld/released results, and PDF/DOCX exports. It verified
server behavior only; it did not verify Sign Out visibility, visual layout,
theme contrast, responsive navigation, save/timer visibility, or accessibility.

## Required execution matrix

Run every row at 1440 px, 768 px, and 390 px in both light and dark themes:

- Landing, Login, Registration, Verification, Help, validation and empty/error
  states.
- Student Dashboard, Join, Instructions, Attempt, save indicator, both timers,
  submit confirmation, withheld result, and released result.
- Professor Dashboard, quiz builder, schedule/timer configuration, publish,
  assignment, results, release, PDF export, and DOCX export.
- Administrator Dashboard with read-only role-scoped information.

At each checkpoint verify no horizontal overflow, clipped actions, excess
right-side whitespace, unbalanced cards, illegible contrast, hidden Sign Out,
or broken mobile navigation. Verify visible keyboard focus, labels, validation,
theme persistence, successful assets, no console errors, no failed requests,
and no unauthorized data in page props or responses.

## Required real workflow

1. Register the exact eligible synthetic Student and privately receive the
   provider-delivered code.
2. Reject invalid, expired, replayed, too-fast resend, and non-roster claims;
   verify successful activation and login.
3. Professor creates, schedules, authors, publishes, and assigns a quiz.
4. Student joins, starts, saves answers, reloads, submits, and sees no score.
5. Professor reviews, releases, and exports PDF/DOCX; Student then sees only
   earned and maximum points.
6. Sign out for every role and inspect both allowed and forbidden navigation.

Screenshots must go to private QA storage and must not contain passwords,
verification codes, API keys, connection strings, or sensitive academic data.
Update this report only with viewport/time/URL evidence captured by an actual
browser.
