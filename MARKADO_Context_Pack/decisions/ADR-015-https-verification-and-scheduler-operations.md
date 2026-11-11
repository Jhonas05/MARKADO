# ADR-015 — HTTPS verification delivery and assessment scheduler operations

- Status: APPROVED implementation contract; external activation remains conditional
- Recorded: 12 November 2026, Asia/Manila
- Authority: product owner's final full live MVP execution request
- Related: ADR-005, ADR-007, ADR-011, ADR-013, ADR-014

## Decision

Hosted MARKADO environments may deliver institutional verification messages
through the Resend HTTPS email API. The adapter uses server-side bearer
authentication, a deterministic non-secret idempotency key, bounded connection
and request timeouts, safe retry behavior, and privacy-safe failure logs. The
API key is deployment-secret configuration and is never stored in Git or sent
to Inertia. The existing Laravel mail adapter remains available for the guarded
loopback demonstration and an approved remote SMTP environment.

Hosted registration fails closed unless the selected delivery driver has a
valid sender and its required secret. Selecting the HTTPS driver does not
weaken exact institutional-domain, trusted-roster provenance/freshness,
mailbox-proof, Student-only activation, duplicate, cooldown, rolling-limit, or
replay controls. Real delivery is not established until a verified sender, API
key, controlled recipient, and provider evidence are observed.

The assessment expiration command remains repeat-safe and serialized by
PostgreSQL attempt locks. Its once-per-minute schedule also uses a shared cache
leader lock and an overlap lock and writes aggregate operational summaries
without attempt, Student, answer, or result content. Deployment requires one
dedicated scheduler process with one replica and a shared database cache.
Railway's native cron minimum is five minutes, so it is not an acceptable
substitute for this requirement.

## Activation consequence

Code and tests may ship default-off. Staging email delivery requires an
authorized Resend key and verified sender. Staging expiration requires a
single `php artisan schedule:work` service, observed logs over multiple runs,
and a real expired synthetic attempt before its flag is enabled. Creating an
additional paid Railway service or changing billing still requires explicit
owner authorization. Production activation additionally remains subject to
institutional identity inputs, live backup/restore evidence, browser QA,
review, exact-candidate CI, and release approval.
