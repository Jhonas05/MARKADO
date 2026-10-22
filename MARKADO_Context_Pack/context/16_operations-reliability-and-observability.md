# Operational Reliability, Monitoring, Recovery and Support

## Reliability goals [REC]
MARKADO is an assessment system: silent data loss during a timed quiz is more serious than a cosmetic error. Prioritize safe writes, explicit acknowledgement, deterministic deadlines, recoverable incidents and controlled results publication.

## Reliability patterns [REC]
- Persist critical start, save, submit and publish actions atomically.
- Separate "click received" from "commit acknowledged" in student UI.
- Define retry semantics for network failure; use idempotency for irreversible actions.
- Limit heavy jobs such as AI generation and PDF/DOCX exports to background queues when appropriate.
- Treat quiz closing and expiry as policy-bound business events, not just UI timeouts.
- Preserve consistent backups and prove restoration under the chosen hosting model.

## Online Laravel runtime responsibilities [REC]
- Define health checks for Laravel HTTP service, relational database, queue workers, scheduler, and private file storage.
- Use multiple web workers only when session, locking, deadline checks and idempotency behavior are consistent across them.
- Separate long AI and export jobs from interactive quiz saves/submissions; monitor retries and dead-letter/failed jobs.
- During an internet outage, preserve honest **unconfirmed save** and retry UX without implying full offline exam operation.
- Production host, backup cadence, high-availability objectives, monitoring provider and restore owner are [OPEN].

## Health and operational dashboards [REC]
Track errors in auth/join, start acceptance, answer save failures, stale attempt rate, deadline enforcement errors, incomplete submission acknowledgements, AI generation failure rate, export failures, unauthorized access denials and queue backlog. Prefer aggregate metrics that exclude student answer content.

## Incident severity rubric [REC]
| Severity | Example | Operating response |
|---|---|---|
| Critical | Cross-student results exposure; corrupted answer finalization | Stop affected operation, isolate, preserve evidence, communicate via approved process |
| High | Active quiz unable to save/submit; timer systemic mismatch | Escalate, diagnose, apply authorized exam policy |
| Medium | One report export format fails | Preserve result data, retry report job, log incident |
| Low | Misaligned label or nonblocking help copy | Queue UX fix and regression check |

## Support playbooks [REC]
**Cannot join:** confirm authorization, published state, schedule, account and attempt policy without asking for credentials. **Time complaint:** inspect trusted start/deadline and server logs, never grant extensions by editing browser timers. **Uncertain submit:** reconcile authoritative attempt state and return same receipt. **Wrong score:** inspect snapshot/rubric and use approved adjustment path with audit reason. **Missing export:** confirm requester permissions and export job status.

## Recovery and backup [OPEN]
Decide recovery point/time objectives, backup cadence, offsite protection, restore ownership, internet/server outage exam policy and reconnection reconciliation with the **central Laravel server**. Fully offline device-result synchronization is out of scope. Production readiness must include practical drills and rollback routes.

## Avoiding false success indicators [REC]
An HTTP 200 response is not proof of grade integrity. Verify database commit, correct owner scope, attempt status and audit event. Do not expose sensitive raw materials or personal information in analytics dashboards or crash logs.
