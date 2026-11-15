# MARKADO staging reactivation checklist

Staging is intentionally stopped for cost control. This checklist does not
authorize a restart, deployment, production change, or new expense. Obtain the
owner's explicit approval for the specific staging activity and its possible
usage charges before executing any start, redeploy, or upload command. Keep
production, the existing staging PostgreSQL service, its volume, credentials,
accounts, and quiz records intact.

## 1. Before spending runtime

1. Verify the owner-approved time window and cost exposure. Railway reported
   that usage alerts require an active subscription; the workspace has no
   verified compute limit. The still-running PostgreSQL service and volume can
   accrue usage. Do not set a workspace hard limit that could stop production.
2. Confirm the repository is `Jhonas05/MARKADO`, PR #36 is open, and its exact
   current head and all required checks are green. Fetch the current `main` and
   recompute the PR merge candidate; do not assume a historical tree is current.
3. Use an isolated clean checkout or artifact of that verified head. Check its
   tracked file list, build instructions and deployment hooks; do not include
   `.env`, credentials, private QA files, or database dumps. Inspect migrations
   and hooks before the approved staging deployment; never reset, seed, or
   restore over the existing database.
4. Verify the target is project `be241212-40d8-43f4-abf2-04efb84d094f`,
   environment `82eb52bf-06e4-4d19-b212-19d2bb652c09` (staging), web service
   `9ad89ef2-bcc1-421c-bfeb-2b6a4dac660d`, scheduler service
   `7fe420aa-0394-4e25-a31b-d6e9eb3aa1ec`, and PostgreSQL service
   `3ad84ebc-b690-4c52-8a9a-4d7c2a242647`. Abort on any mismatch.
5. The last verified web image was built before the subsequent local mobile
   navigation fix. Verify the final candidate and use an explicitly authorized
   deployment if the presentation must include newer changes. Never assume a
   stopped deployment is the current PR head.

## 2. Scheduler image mismatch: mandatory gate

The attempt to scale the stopped scheduler to zero replaced the previous
deployment. Railway marked deployment `0f589d46-27ef-448e-bbf5-538693b8ef00`
removed and created deployment `5afa565b-ea8a-433e-b43f-8572e0993aad` with a
different image digest. The replacement is stopped. The service's original
one-replica `sfo` configuration was restored with deployment skipped.

Before any authorized scheduler restart, compare the deployment source,
image digest, build manifest, start command, and effective region with the
verified candidate and the prior scheduler artifact. Do not infer source
identity merely from a successful build. If the new digest cannot be explained
and tied to the verified candidate, deploy a verified, reviewed artifact only
after explicit approval, and recheck its image. Never start a second worker.

## 3. Short, authorized staging window

1. With explicit approval and exact staging IDs, start or deploy only the
   required staging web service. Confirm health at
   `https://markado-app-staging.up.railway.app/up`, expected deployment and
   candidate identity, and a read-only PostgreSQL identity/migration check.
   Do not display secrets, student data, or answer keys.
2. Verify Student, Professor, and Administrator login with existing synthetic
   accounts. Check the assigned presentation quiz and its closing time without
   starting its unused attempt. Keep the completed, released synthetic result
   available as the fallback. If the quiz is no longer available, diagnose
   safely; do not overwrite existing records.
3. If registration is to be demonstrated, first verify the authorized Resend
   key, provider-verified sender, controlled inbox, roster provenance and
   freshness, and staging-only flags. Test one separately authorized synthetic
   signup and mailbox verification; confirm Student-only activation without
   Professor approval. Keep hosted registration gated if any check fails.
4. Once its image is verified and its runtime explicitly approved, start only
   one scheduler replica. Confirm the staging-only worker flag, shared cache,
   minute-by-minute aggregate logs, and an unattended **separate synthetic**
   expiry without reopening its Student attempt. Keep request-time deadline
   enforcement authoritative. If the image or worker checks fail, stop the
   affected worker and report it; do not claim background expiry is verified.
5. Rehearse an isolated synthetic Professor create/publish/assign -> Student
   join/save/submit -> Professor score/release -> Student result flow. Confirm
   ownership boundaries, PDF/DOCX exports, 1440/768/390 layouts, light/dark,
   keyboard focus, and persistence after an authorized staging app restart.
   Preserve the unused presentation attempt and completed fallback.
6. Record the exact candidate, deployment IDs, image digests, health, tests,
   scheduler evidence and unresolved gates in PR #36 without private data.
   Stop the staging web and worker again at the end of the approved window.
   Do not stop or delete PostgreSQL or its volume without separate instruction.

## 4. Remaining release gates

- Hosted Student registration and real mailbox delivery are blocked until the
  authorized Resend configuration and controlled inbox are verified.
- A real staging backup and isolated live-data restore remain blocked until
  custody, encryption-key recovery, off-volume destination, secure connection,
  retention, and restore safeguards are verified. The synthetic local drill is
  not a staging backup. Do not expose PostgreSQL or bypass SSH host checks.
- Production promotion additionally requires institutional authorization,
  recovery, security/accessibility review, exact-tree CI, and separate owner
  release approval. Never merge PR #36 or deploy production automatically.

The local-only demo and its private credentials are described in the operator's
local `LOCAL-DEMO-RUNBOOK.md`; no passwords or quiz codes belong in this file.
