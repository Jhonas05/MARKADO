# Phase 5B product-owner approval checklist

This checklist proposes small, practical MVP defaults. Every item is **OPEN**.
Checking an option here is not sufficient approval: record the selected option,
approver, and date in `MARKADO_Context_Pack/context/18_open-decisions.md` before
implementing the policy.

## Identity and registration — D02, D13, D14

- [ ] **Option A — single institution MVP (recommended):** institution-issued
  student number plus email; both unique within that institution; staff accounts
  provisioned by an authorized administrator; recovery through verified email.
- [ ] **Option B — multi-institution:** institution membership scopes student
  numbers, emails, courses, and staff privileges from day one.
- [ ] **Option C — invitation only:** no public student registration; authorized
  staff import or invite every account while identity rules are validated.

Still decide who verifies student-number ownership, whether email verification
is mandatory, and how duplicate or changed identities are reviewed and merged.

## V1 question formats — D03

- [ ] **Option A — objective core (recommended):** single-answer multiple choice,
  true/false, and short answer requiring manual review.
- [ ] **Option B — smallest release:** single-answer multiple choice only.
- [ ] **Option C — expanded:** add multiple-select and essay, with explicit partial
  credit and manual-grading rules.

## Timers — D04–D06

- [ ] **Option A — whole-quiz timer (recommended):** one server deadline; warn in
  the UI and auto-submit the latest server-saved answers at expiry.
- [ ] **Option B — no timer MVP:** schedule controls availability, but attempts have
  no countdown.
- [ ] **Option C — combined timers:** per-question plus whole-quiz limits, requiring
  precedence, navigation, accommodation, and expiry decisions.

## Attempts, closing, connectivity, and timezone — D07–D10

- [ ] **Option A — conservative online MVP (recommended):** one resumable attempt;
  deny starts that cannot finish before close; save each answer; reject writes
  after the server deadline; store UTC and display the course timezone.
- [ ] **Option B — hard close:** allow starts until close, then auto-submit at the
  closing instant; one resumable attempt.
- [ ] **Option C — grace policy:** allow a documented grace/reconnection window and
  record late events for instructor review.

Also approve what the student sees during an outage and who may grant a retry.

## Results and ranking — D11, D12

- [ ] **Option A — controlled release (recommended):** show score only after an
  instructor release action; hide answer keys by default; no student ranking.
- [ ] **Option B — immediate score:** release objective score after submission but
  withhold answers/explanations until the quiz closes.
- [ ] **Option C — scheduled release:** publish approved feedback at a configured
  time; ranking remains instructor-only unless separately approved.

If ranking is approved, define ties, repeat-attempt aggregation, missing manual
grades, and whether names may appear in any student-facing view.

## Permissions and eligibility — D15, D22, D23

- [ ] **Option A — scoped ownership (recommended):** instructors manage only
  assigned courses/quizzes; students access enrolled, eligible quizzes; academic
  administrators oversee accounts but cannot silently change grades.
- [ ] **Option B — department scope:** designated coordinators share quiz and result
  access within a department, with audited membership.
- [ ] **Option C — institution-wide administration:** explicitly named staff may
  manage all records, with reasons and audit events for sensitive actions.

Separately approve teaching assistants, enrollment source, regrade/override
authority, required reasons, and impersonation policy. Role checks alone never
replace resource ownership policies.

## Runtime compatibility — D29

- [ ] **Option A — approve current baseline (recommended for MVP):** PHP 8.2,
  Laravel 12, React 19, Inertia 3, Node 22, and PostgreSQL 17 for development/CI.
- [ ] **Option B — upgrade before feature work:** approve a tested newer PHP and
  Laravel baseline, including workstation and CI migration.
- [ ] **Option C — time-boxed baseline:** approve Option A through the MVP with a
  dated dependency and security review before deployment.

Production PostgreSQL hosting, backups, recovery objectives, and service version
remain part of D28 even if a D29 development baseline is approved.
