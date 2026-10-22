# ADR-001: Online Web Deployment with Laravel

**Status:** DECIDED by user  
**Date:** 22 October 2026  
**Scope:** MARKADO development direction; no implementation or production claim.

## Context
The original MARKADO PDF left deployment unspecified and did not select an application framework. The user has now clarified: **"online pre, tapos laravel sha"** (online and Laravel).

## Decisions
1. Build **MARKADO as an internet-hosted online web app**, with centralized registration, assessment attempts, deadlines, quiz results and reports.
2. Use **Laravel (PHP)** as the web application's backend/framework.
3. Do not include fully offline per-device examination, LAN-only architecture, offline grade synchronization, or other deployment modes in the agreed V1 direction without another explicit product decision.

## Consequences and guidelines [REC]
- The Laravel server, backed by centralized persisted data, must be the authority for access rights, timing, accepted saves and final grades. Browser countdowns only display remaining time.
- Student number + email uniqueness should be enforced with database constraints as well as application validation.
- Use transaction/idempotency protections for simultaneous submit/expiry events.
- When connectivity is lost, never falsely claim answers are safely saved; an outage/reconnection policy still needs academic approval.
- A modular Laravel monolith with queued AI-material processing and PDF/DOCX exports is a recommendation, not a user-confirmed implementation detail.

## Subsequent decision update (22 October 2026)
**D26 PostgreSQL** and **D27 React + Inertia.js** are now **DECIDED** in `ADR-002-postgresql-react-inertia.md`; the earlier statement that database and UI were open has been superseded.

## Still OPEN
**D28** production host, PostgreSQL placement, storage, queues, monitoring and backup goals; **D29** exact Laravel/PHP and frontend dependency compatibility. Also unresolved: timer behavior, attempt limits, close-time policy, AI provider, academic/result-release policy, school scope and privacy retention.

## Revisit if
Explicit user product approval changes the deployment requirements (e.g., offline exam support) or framework selection.
