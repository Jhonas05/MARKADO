# ADR-013 — v9.4 guarded local Student self-registration

- Status: APPROVED for the local synthetic presentation scope below
- Recorded: 2 November 2026, Asia/Manila
- Authority: product owner's MARKADO v9.4 implementation request
- Related: ADR-008–011 identity controls; ADR-012 cosmetic rewards; approved hourly-policy material in unmerged PR #24

The product owner authorizes a working Student registration, mailbox verification, automatic activation, and assigned-quiz flow for the November 8 local presentation. Real school operations and public production registration remain unapproved.

## Local workflow

1. Collect exactly official Student Number, institutional Email, Password, and Confirm Password. Do not require a full name or accept a role/status. Use the neutral display name `Student` because the current roster has no authorized display-name field.
2. Match an existing active verified trusted roster record, with exact official number (including zeros) and the same normalized institutional mailbox. Preserve the local part; normalize only the domain under the existing email policy. Reject case-ambiguous existing mailbox ownership conservatively. Registration never creates or approves roster evidence.
3. Under a PostgreSQL roster row lock and mailbox serialization, create one pending Student and reserve the existing roster's unique account association. Leave `linked_at` unset and all roster verification fields unchanged. Duplicate/retry/conflicting claims never update passwords, transfer ownership, or create another pending account. Existing unique constraints remain authoritative; no new migration is required.
4. Issue the existing random, single-use, keyed-hash-at-rest 30-minute mailbox challenge. The code is delivered through SMTP to a loopback Mailpit inbox, entered in a masked verification form, and posted in a CSRF-protected request. It never appears in URLs, shared Inertia props, audit events, source, or flashed input.
5. On successful consumption, recheck the existing registration-claim verifier and Student activation service in the same transaction. Activation sets the roster link timestamp and active Student status once. Mailbox proof alone grants no workspace or quiz access. Disabled, disputed, inactive, foreign, or conflicting records remain denied.
6. Permit Professor assignment to an unclaimed active verified roster identity only in this guarded environment. It grants no Student access until activation and the existing eligibility gate succeed. Registered but unverified accounts remain blocked.

## Reconciled hourly policy

The approved PR #24 material sets a maximum of **five initial issues plus resends per rolling 60 minutes**. A lower positive integer is permitted; invalid, non-integer, or above-five values fail closed. Cooldown remains 60 seconds. The exact rolling boundary is `issued_at > server_now - 60 minutes`.

Reuse the relevant PR #24 safeguards without merging that PR: serialize by account and normalized mailbox; count failed delivery issuance rows; revoke failed, changed-address, disabled, or delivery-expired challenges; confirm delivery using a fresh server timestamp. A delayed delivery cannot extend token validity. ADR-011's previous open-hourly statement is superseded only for this approved ceiling.

PR #24 used ADR-012 for its hourly decision; current main already uses ADR-012 for gamification. This ADR preserves that merged identifier and records the reconciled policy without renumbering history or importing unrelated PR changes.

## Guard and local anti-abuse settings

The dedicated presentation flag and all registration/verification/activation flags default false. Enabling them alone is insufficient: require local Laravel, loopback HTTP/DB/SMTP origin, PostgreSQL schema `markado_demo_v89_registration_*`, and its exact command-owned guard marker. No SMTP URL override or log mailer is permitted. Automated tests are limited to in-memory SQLite or named disposable PostgreSQL test schemas/CI database.

Local HTTP limits are 10 registration requests/minute per network and 5/minute per private claim fingerprint, plus 10 verification actions/minute per pending session identity/network. The durable five/hour mailbox ceiling remains separate. These are local presentation controls, not an approved school-wide monitoring policy.

## Exclusions and acceptance

No production roster source, real domain, external mail provider, recovery/reset, duplicate/dispute reassignment, staff signup, protected-schema migration, scheduler activation, or deployment is authorized. Incomplete pending claims require sign-in with their original password to resume verification; ownership recovery remains a school decision.

Acceptance covers required/invalid fields, exact number preservation, duplicate number/email, concurrent claims, generic rejected outcomes, hash-only secrets, wrong/expired/replayed codes, CSRF/throttling, no role escalation, both assignment orders, unassigned denial, real mailbox consumption and automatic activation, and the existing one-attempt/scoring/manual-release/cosmetic flow. Preserve all v9.3 presentation data as fallback.
