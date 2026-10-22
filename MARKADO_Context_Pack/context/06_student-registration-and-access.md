# Student Enrollment Identity, Registration and Quiz Joining

## Baseline [REQ]
Student registration requires **student number** and **email address** to reduce duplicate accounts. Code/link provides quiz access navigation, but authentication and authorization must still be evaluated.

## Registration flow [REC]
1. Collect student number + email with clear field labels and privacy notice.
2. Normalize values carefully; e.g. trim permitted whitespace and compare email consistently without assuming that all address local parts can be freely rewritten.
3. Validate format and institutional number rules once defined.
4. Transactionally check unique key(s) in database - front-end validation alone is insufficient.
5. Verify email/student identity only according to approved school policy.
6. Create account and issue a non-enumerating verification/recovery status.
7. Record creation and email verification events where applicable.

## Proposed identity and uniqueness invariants [REC]
- Student number must have a **defined uniqueness scope**: institution, campus, or global [OPEN].
- Email uniqueness must also have a defined scope, particularly if guardian/shared mailboxes are possible [OPEN].
- Server-side unique constraints handle concurrent double submits.
- Do not equate an email with proof that a specific student number belongs to the sender.
- Account correction/merge actions preserve audit evidence; never silently move grades between identities.

## Quiz join gate [REC]
`resolve code/link -> authenticate -> resource scope/eligibility -> quiz published -> availability window -> attempt policy -> instructions -> start`

Do not expose quiz question/answer details to unauthorized link visitors. Distinguish inaccessible link from invalid authentication in user-friendly terms without confirming another person's account existence.

## Example messages [REC]
- "Please enter your student number and school email." (The school-email limitation is [OPEN]; use "email" if not required.)
- "We couldn't complete registration with those details. Check your information or use account recovery."
- "This quiz is not available right now. Check the schedule shown by your instructor."
- "You're already taking this quiz. Continue your existing attempt." (Only when resume is supported and authorized.)

## Cases that need product decisions [OPEN]
Transferred student; two institutions using identical student numbers; one student changing email; instructor registering under student email; typo correction; unverified school records; deleted account later reused; one email shared by siblings; academic-year rollover. These are identity-policy choices, not merely coding errors.
