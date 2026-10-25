# Student Enrollment Identity, Registration and Quiz Joining

## Baseline [REQ]
Student onboarding requires an official **student number** and a verified,
school-issued **institutional email address** that match the same authoritative
school roster identity (ADR-008). Personal mailboxes are not accepted for V1.
ADR-009 approves Student self-registration as the normal V1 issuance direction.
ADR-010 approves automatic Student-only activation after every independently
approved identity, account-state, security, and anti-abuse gate succeeds.
Code/link provides quiz access navigation, but authentication, identity,
assignment, and authorization must still be evaluated.

## Registration flow [DECIDED direction + OPEN mechanisms]
1. Collect student number + email with clear field labels and privacy notice.
2. Normalize values carefully; e.g. trim permitted whitespace and compare email consistently without assuming that all address local parts can be freely rewritten.
3. Validate format and institutional number rules once defined.
4. Transactionally check unique key(s) in database - front-end validation alone is insufficient.
5. Verify institutional mailbox possession and require the number/email pair to
   match the same trusted school record. The token/delivery mechanism remains
   open under D14.
6. Recheck current roster/account evidence and the approved security gate.
7. Atomically link and activate only a pending Student; record a privacy-safe event.
8. Apply quiz eligibility independently. Activation alone grants no quiz access.

The pending-claim creation point, verification challenge/provider, rate limits,
recovery, and school roster operations remain OPEN. Public mutation routes stay
disabled until those mechanisms and operations are separately approved.

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
- "Please enter your student number and school-issued institutional email."
- "We couldn't complete registration with those details. Check your information or use account recovery."
- "This quiz is not available right now. Check the schedule shown by your instructor."
- "You're already taking this quiz. Continue your existing attempt." (Only when resume is supported and authorized.)

## Cases that still need school/product decisions [OPEN]
Transferred student; two institutions using identical student numbers; one student changing email; instructor registering under student email; typo correction; unverified school records; deleted account later reused; one email shared by siblings; academic-year rollover. These are identity-policy choices, not merely coding errors.
