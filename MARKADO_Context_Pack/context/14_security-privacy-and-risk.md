# Security, Student Privacy and Assessment Integrity

## Trust boundaries [REC]
Student browsers/devices, instructor browsers, server/API, database, file upload storage, optional AI provider, export/download location, and administrative tools all have different trust levels. Client controls improve UX but cannot be the sole enforcement of permissions, timers or grades when a trusted server is available.

## Security baseline [REC]
- Authentication with secure session handling, credential recovery and reasonable rate limits.
- Authorization at the record and action level for every server endpoint and export.
- Query scoping for owner/class/institution boundaries.
- Robust validation and output encoding to resist malicious text in questions/materials.
- Controlled upload MIME/type/size limits, scanning and safe storage.
- Secrets not embedded in mobile/web client bundles or PDF exports.
- Sensitive data encrypted in transit and appropriate storage protections.
- Audits for access-control changes, publishing, attempts, grade changes and downloads.
- Backups with verified restore tests and access limited to authorized operators.

## Laravel application security checklist [REC]
- Use server-enforced Laravel **policies/gates** (or equivalent tests) for quiz, question, attempt, grade and export ownership. A correct URL or quiz code never bypasses access rules.
- Validate inbound data with Laravel server validation/form requests, but enforce critical uniqueness and idempotency with database constraints/transactions too.
- With **Laravel + React/Inertia.js [DECIDED]**, use secure Laravel session authentication, CSRF protections for form/actions, correct cookie security/same-site settings, and server policies on **every** attempt/result endpoint. Prevent leaked credentials through debug pages and `.env` exposure. Inertia shared props and history must not reveal answer keys or unreleased student grades.
- Keep original teaching material, AI draft sources, exam answer keys and exports in private storage with authorized access and appropriate expiration.
- Limit and log sensitive actions and queue jobs; control file type, file path traversal, upload processing and prompt injection from untrusted content.

## Threat scenarios and mitigations [REC]
| Threat | Potential harm | Candidate mitigation |
|---|---|---|
| Guessing quiz codes | Unauthorized quiz discovery | Sufficient entropy, rate limits, eligibility checks |
| Account enumeration | Revealing student registration | Generic errors, safe recovery, throttling |
| IDOR on attempt/results | Accessing other grades | Server-side resource authorization tests |
| Manipulating browser time | Gaining extra quiz time | Laravel/server-enforced persisted deadlines; countdown is display-only |
| Submit retry race | Duplicate grade / inconsistent state | Transactional state transition + idempotency |
| Answer key leakage | Compromised exam integrity | Server-only answer key, minimal attempt payload |
| Malicious source documents | Prompt injection / malicious files | Isolated extraction, instructions/data separation, scanners |
| Forged admin role | Unauthorized reports | Server role source, scoped permissions, audit |
| Lost export files | Exposure of learner data | Download policy, short-lived links, retention rules |
| Wrong answer from AI | Misgraded students | Human review, provenance and correction workflow |

## Philippine privacy context - review with school [REC]
MARKADO is likely to process identifiable student information and education-related records. Philippine **Republic Act No. 10173 (Data Privacy Act of 2012)** and National Privacy Commission guidance are relevant references; this pack is **not legal advice**. Confirm the school/controller's lawful processing basis, notice, collection, retention, third-party transfer, data-subject rights and incident procedures before rollout. See `19_resources-and-source-notes.md` for official links.

## Retention matrix to approve [OPEN]
| Data | Proposed review questions |
|---|---|
| Student registration | Retain how long after transfer/graduation? |
| Question banks/source uploads | May files include copyrighted third-party content? Who owns them? |
| Raw attempts and answers | What is academic grade appeal/record policy? |
| AI generation requests/output | May the provider store input/output? How long? |
| Audit events | What incident/review retention is justified? |
| Export files | Delete after download or short expiry? |
| Backups | Retention, encryption and deletion propagation? |

## Assessment-integrity policy [REC]
Fairness needs clear start/end rules, reviewer-controlled questions, consistent scoring and supported accommodation choices. Randomization and codes reduce casual misuse but cannot prove academic honesty. Do not claim cheating-proof operation or collect intrusive device telemetry without an explicit policy and demonstrated need.

## Security release gate [REC]
Block production if there are unresolved cross-user grade leaks, bypassable timers, insecure upload execution, leaked answer keys, corrupt retries, or inability to recover data from backup. Use risk acceptance with named owner for noncritical issues.
