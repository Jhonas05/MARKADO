# AI-Assisted Question Generation from Materials

## Requested workflow [REQ]
Authorized professor/admin uploads or supplies learning material, requests question generation, inspects editable draft questions and **approves them manually** before publication. This is a drafting assistant, not an autonomous assessment authority.

## Recommended pipeline [REC]
1. **Authorize** uploader against intended quiz/material scope.
2. **Accept and scan** file under approved format, size and malware policy.
3. **Extract** machine-readable text; mark partial extraction and inaccessible figures instead of hallucinating missing context.
4. **Segment** material into attributable source spans (page/section where possible).
5. **Specify** generation target: topic, count, candidate question types, language and difficulty descriptors.
6. **Generate** structured draft objects, not free-text blobs.
7. **Validate** schema, option count, correct answer existence, duplications and source alignment.
8. **Review** with instructor-facing source excerpt, editable answer key and reject/regenerate options.
9. **Approve** selected question revisions with reviewer and timestamp.
10. **Publish** only approved questions through normal quiz checks.

## Example conceptual draft fields [REC]
`generation_job_id`, `material_id`, `source_page_or_section`, `draft_question_id`, `prompt`, `type`, `options`, `proposed_answer`, `explanation`, `review_status`, `reviewer_id`, `reviewed_at`, `revision_id`.

## Safeguards [REC]
- Treat document contents as untrusted **source text**, never as instructions to bypass permissions or publish answers.
- Never send entire student rosters, grades, or unnecessary identifying data to an AI provider.
- Never silently publish unreviewed output, even if model confidence appears high.
- Let instructors reject a question for ambiguity, invented information, wrong answer, grade-level mismatch or duplicate concepts.
- Mark unsupported generation when source is too short, scanned without extraction, unreadable, or irrelevant.
- Keep enough provenance for a human to check, without making unsupported claims about certainty.

## Operational limits [OPEN]
Accepted file types (e.g. PDF, DOCX, text), size/pages, image/OCR scope, number of draft questions, provider, data-transfer and storage approval, source deletion policy, usage quota, expected latency, online provider connectivity, languages, accessibility requirements, and costs.

## Generator evaluation plan [REC]
Sample diverse instructor-authored materials. Score each generated question for factual support, single unambiguous answer, plausible distractors, alignment to level/outcomes, language quality, accessibility and duplicate rate. Require instructor signoff. Track failures to improve prompts and validation, not to claim zero hallucinations.

## Relationship to approved platform [DECIDED + REC]
MARKADO is an **online Laravel web app**. Use **Laravel-managed background jobs** as a recommended pattern for extraction and question-generation work; do not make quiz-taking depend on the AI provider being available. Provider, approval for transmitting uploads, usage budget, queue backend, OCR libraries, retention and timeout/retry rules remain [OPEN]. This is an architectural recommendation, not a declaration that a particular AI service is installed.
