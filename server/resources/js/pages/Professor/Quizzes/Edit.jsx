import { Link, router, useForm, usePage } from '@inertiajs/react'
import { useState } from 'react'
import Icon from '@prototype/components/Icon.jsx'
import ProfessorLayout from '../../../Layouts/ProfessorLayout.jsx'
import { FieldError, PolicyBanner, ScreenHeader, StatusBadge } from '../../../Components/ProfessorUi.jsx'

const emptyMcqChoices = () => [
  { choice_text: '', is_correct: true },
  { choice_text: '', is_correct: false },
  { choice_text: '', is_correct: false },
  { choice_text: '', is_correct: false },
]

const trueFalseChoices = () => [
  { choice_text: 'True', is_correct: true },
  { choice_text: 'False', is_correct: false },
]

function questionData(question) {
  if (!question) return { type: 'multiple_choice', prompt: '', choices: emptyMcqChoices() }

  return {
    type: question.type,
    prompt: question.prompt,
    choices: question.choices.map((choice) => ({
      id: choice.id,
      choice_text: choice.choiceText,
      is_correct: choice.isCorrect,
    })),
  }
}

function BuilderSteps({ published }) {
  return (
    <nav className="prof-builder-steps" aria-label="Quiz builder steps">
      {['Identity', 'Questions', 'Rules', 'Preview', 'Publish'].map((label, index) => {
        const active = index < 2 || (index === 4 && published)
        return (
          <span className={active ? 'prof-builder-step prof-builder-step--active' : 'prof-builder-step prof-builder-step--locked'} key={label}>
            <span>{index + 1}</span>
            <strong>{label}</strong>
          </span>
        )
      })}
    </nav>
  )
}

export default function EditQuiz({ quiz, questionTypes }) {
  const { errors } = usePage().props
  const editable = quiz.authoring.editable
  const [selectedId, setSelectedId] = useState(quiz.questions[0]?.id ?? null)
  const [copied, setCopied] = useState(false)
  const selected = quiz.questions.find((question) => question.id === selectedId) ?? null
  const details = useForm({
    title: quiz.title,
    course_code: quiz.courseCode,
    course_name: quiz.courseName ?? '',
    instructions: quiz.instructions ?? '',
    opens_at: quiz.schedule.opensAt ?? '',
    closes_at: quiz.schedule.closesAt ?? '',
    whole_quiz_duration_minutes: quiz.schedule.wholeQuizDurationSeconds
      ? Math.round(quiz.schedule.wholeQuizDurationSeconds / 60)
      : '',
    per_question_duration_seconds: quiz.schedule.perQuestionDurationSeconds ?? '',
    randomize_questions: quiz.schedule.randomizeQuestions,
    randomize_choices: quiz.schedule.randomizeChoices,
  })
  const questionForm = useForm(questionData(selected))
  const choiceErrors = Object.entries(questionForm.errors)
    .filter(([key]) => key === 'choices' || key.startsWith('choices.'))
    .map(([, message]) => message)

  function selectQuestion(question) {
    setSelectedId(question.id)
    questionForm.setData(questionData(question))
    questionForm.clearErrors()
  }

  function startQuestion() {
    if (!editable) return
    setSelectedId(null)
    questionForm.setData(questionData(null))
    questionForm.clearErrors()
  }

  function changeType(type) {
    questionForm.setData({
      ...questionForm.data,
      type,
      choices: type === 'true_false' ? trueFalseChoices() : emptyMcqChoices(),
    })
  }

  function updateChoice(index, field, value) {
    questionForm.setData('choices', questionForm.data.choices.map((choice, choiceIndex) => {
      if (field === 'is_correct') return { ...choice, is_correct: choiceIndex === index }
      return choiceIndex === index ? { ...choice, [field]: value } : choice
    }))
  }

  function saveQuestion(event) {
    event.preventDefault()
    if (!editable) return
    const options = { preserveScroll: true }

    if (selectedId) {
      questionForm.put(`${quiz.authoring.questionsPath}/${selectedId}`, options)
      return
    }

    questionForm.post(quiz.authoring.questionsPath, {
      ...options,
      onSuccess: () => {
        setSelectedId(null)
        questionForm.setData(questionData(null))
        questionForm.clearErrors()
      },
    })
  }

  function removeQuestion() {
    if (!editable || !selected || !window.confirm(`Remove question ${selected.position} from this draft?`)) return
    router.delete(`${quiz.authoring.questionsPath}/${selected.id}`, {
      preserveScroll: true,
      onSuccess: () => startQuestion(),
    })
  }

  function removeQuiz() {
    if (!window.confirm(`Delete the private draft “${quiz.title}”? This cannot be undone.`)) return
    router.delete(`/professor/quizzes/${quiz.id}`)
  }

  function publishQuiz() {
    const message = quiz.publication.currentRevisionNumber
      ? 'Publish this draft as the new current immutable revision? Existing revisions and attempt references will be preserved.'
      : 'Publish an immutable snapshot of this draft? Editing it later will require a separate revision.'
    if (window.confirm(message)) router.post(`/professor/quizzes/${quiz.id}/publish`, {}, { preserveScroll: true })
  }

  function unpublishQuiz() {
    if (window.confirm('Unpublish this quiz? New joins will be blocked while revisions and historical records stay intact.')) {
      router.post(`/professor/quizzes/${quiz.id}/unpublish`, {}, { preserveScroll: true })
    }
  }

  function createDraftRevision() {
    if (window.confirm('Create an editable draft copied from the current published revision? The live revision will not change.')) {
      router.post(`/professor/quizzes/${quiz.id}/draft-revisions`, {}, { preserveScroll: true })
    }
  }

  async function copyCode() {
    if (!window.navigator.clipboard) return
    await window.navigator.clipboard.writeText(quiz.access.code)
    setCopied(true)
    window.setTimeout(() => setCopied(false), 1800)
  }

  const revisionLabel = quiz.authoring.revisionNumber
    ? `Revision ${quiz.authoring.revisionNumber}`
    : 'Initial draft'

  return (
    <ProfessorLayout title="Quiz builder">
      <PolicyBanner />
      <ScreenHeader
        eyebrow={`${quiz.courseCode} · ${quiz.statusLabel}`}
        title={quiz.title}
        description="Configure immutable schedules, timers, and randomization. The assessment flow is implemented behind a separate operational activation gate."
        actions={(
          <>
            <Link className="button button--secondary" href="/professor/quizzes">
              <Icon name="arrowLeft" size={18} /> All quizzes
            </Link>
            <Link className="button button--secondary" href={quiz.resultsPath}>
              <Icon name="barChart" size={18} /> Results
            </Link>
          </>
        )}
      />
      <BuilderSteps published={quiz.status === 'published'} />

      <section className="prof-panel prof-authoring-summary" aria-label="Publication status">
        <div><span className="section-kicker">Reserved access code</span><strong>{quiz.access.code}</strong></div>
        <div><span>Student route</span><strong>{quiz.access.path}</strong></div>
        <div><span>Authoring record</span><strong>{revisionLabel}</strong></div>
        <StatusBadge tone={quiz.status === 'published' ? 'success' : 'warning'}>{quiz.statusLabel}</StatusBadge>
        <button className="button button--secondary button--compact" type="button" onClick={copyCode}>
          <Icon name={copied ? 'check' : 'copy'} size={16} /> {copied ? 'Copied' : 'Copy code'}
        </button>
      </section>

      <section className="prof-panel prof-publication-card" aria-labelledby="publication-title">
        <div className="prof-panel-heading">
          <div><p className="section-kicker">D16 approved boundary</p><h2 id="publication-title">Publication and revision history</h2></div>
          {quiz.publication.hasUnpublishedChanges ? <StatusBadge tone="warning">Draft changes</StatusBadge> : null}
        </div>
        <p className="prof-publication-copy">
          Published revisions cannot be edited or deleted. A new draft is copied from the current revision and becomes visible to eligible students only after explicit publication.
        </p>
        {errors?.publication ? <p className="prof-validation-summary" role="alert">{errors.publication}</p> : null}
        {Object.entries(errors ?? {}).filter(([key]) => key.startsWith('questions.')).map(([key, message]) => (
          <p className="prof-validation-summary" role="alert" key={key}>{message}</p>
        ))}
        <div className="prof-revision-list" aria-label="Revision history">
          {quiz.publication.revisions.length ? quiz.publication.revisions.map((revision) => (
            <div key={revision.id}>
              <strong>Revision {revision.number}</strong>
              <span>{revision.status === 'published' ? 'Immutable published snapshot' : 'Editable draft'}</span>
              <StatusBadge tone={revision.status === 'published' ? 'success' : 'warning'}>{revision.status}</StatusBadge>
            </div>
          )) : <p>No revision has been published yet.</p>}
        </div>
        <div className="prof-publication-actions">
          {quiz.publication.canUnpublish ? <button className="button button--danger-soft" type="button" onClick={unpublishQuiz}>Unpublish</button> : null}
          {quiz.publication.canCreateDraft ? <button className="button button--secondary" type="button" onClick={createDraftRevision}>Create draft revision</button> : null}
          {quiz.publication.canPublish ? (
            <button className="button button--primary" type="button" onClick={publishQuiz}>
              {quiz.status === 'unpublished' && !quiz.publication.hasUnpublishedChanges
                ? `Republish revision ${quiz.publication.currentRevisionNumber}`
                : `Publish ${revisionLabel}`}
            </button>
          ) : null}
        </div>
      </section>

      <section className="prof-panel prof-assignment-preview" aria-labelledby="assignment-title">
        <div>
          <p className="section-kicker">D22 school-managed eligibility</p>
          <h2 id="assignment-title">Assignment preview</h2>
          <p>Eligibility is evaluated from verified, active school records. Assignment management stays unavailable until D13–D15 permissions are approved.</p>
        </div>
        <dl>
          <div><dt>Direct students</dt><dd>{quiz.assignments.directStudentCount}</dd></div>
          <div><dt>Active sections</dt><dd>{quiz.assignments.sections.length}</dd></div>
        </dl>
        <div className="prof-assignment-sections">
          {quiz.assignments.sections.length ? quiz.assignments.sections.map((section) => (
            <span key={section.code}><strong>{section.code}</strong>{section.name}</span>
          )) : <span>No section assignments recorded.</span>}
        </div>
      </section>

      <section className="prof-panel prof-details-card" aria-labelledby="quiz-details-title">
        <div className="prof-panel-heading">
          <div><p className="section-kicker">{revisionLabel}</p><h2 id="quiz-details-title">Quiz details</h2></div>
          <StatusBadge tone={editable ? 'warning' : 'success'}>{editable ? 'Editable' : 'Immutable'}</StatusBadge>
        </div>
        {!editable ? <p className="prof-immutable-note"><Icon name="lock" size={17} /> This published revision is read-only. Create a draft revision to make changes.</p> : null}
        <form onSubmit={(event) => {
          event.preventDefault()
          if (editable) {
            details.transform((data) => ({
              ...data,
              whole_quiz_duration_seconds: data.whole_quiz_duration_minutes
                ? Number(data.whole_quiz_duration_minutes) * 60
                : null,
              per_question_duration_seconds: data.per_question_duration_seconds
                ? Number(data.per_question_duration_seconds)
                : null,
            })).put(quiz.authoring.detailsPath, { preserveScroll: true })
          }
        }}>
          <div className="prof-field-grid">
            <label className="prof-field prof-field--wide"><span>Quiz title</span><input disabled={!editable} value={details.data.title} onChange={(event) => details.setData('title', event.target.value)} aria-invalid={Boolean(details.errors.title)} /><FieldError message={details.errors.title} /></label>
            <label className="prof-field"><span>Course code</span><input disabled={!editable} value={details.data.course_code} onChange={(event) => details.setData('course_code', event.target.value)} aria-invalid={Boolean(details.errors.course_code)} /><FieldError message={details.errors.course_code} /></label>
            <label className="prof-field"><span>Course name <small>(optional)</small></span><input disabled={!editable} value={details.data.course_name} onChange={(event) => details.setData('course_name', event.target.value)} aria-invalid={Boolean(details.errors.course_name)} /><FieldError message={details.errors.course_name} /></label>
            <label className="prof-field prof-field--wide"><span>Student-facing instructions <small>(optional)</small></span><textarea disabled={!editable} rows="3" value={details.data.instructions} onChange={(event) => details.setData('instructions', event.target.value)} aria-invalid={Boolean(details.errors.instructions)} /><FieldError message={details.errors.instructions} /></label>
            <label className="prof-field"><span>Opens (Asia/Manila)</span><input disabled={!editable} type="datetime-local" value={details.data.opens_at} onChange={(event) => details.setData('opens_at', event.target.value)} aria-invalid={Boolean(details.errors.opens_at)} /><FieldError message={details.errors.opens_at} /></label>
            <label className="prof-field"><span>Closes (Asia/Manila)</span><input disabled={!editable} type="datetime-local" value={details.data.closes_at} onChange={(event) => details.setData('closes_at', event.target.value)} aria-invalid={Boolean(details.errors.closes_at)} /><FieldError message={details.errors.closes_at} /></label>
            <label className="prof-field"><span>Whole-quiz timer <small>(minutes, optional)</small></span><input disabled={!editable} type="number" min="1" max="1440" value={details.data.whole_quiz_duration_minutes} onChange={(event) => details.setData('whole_quiz_duration_minutes', event.target.value)} aria-invalid={Boolean(details.errors.whole_quiz_duration_seconds)} /><FieldError message={details.errors.whole_quiz_duration_seconds} /></label>
            <label className="prof-field"><span>Per-question timer <small>(seconds, optional)</small></span><input disabled={!editable} type="number" min="1" max="3600" value={details.data.per_question_duration_seconds} onChange={(event) => details.setData('per_question_duration_seconds', event.target.value)} aria-invalid={Boolean(details.errors.per_question_duration_seconds)} /><FieldError message={details.errors.per_question_duration_seconds} /></label>
            <label className="prof-setting-check"><input disabled={!editable} type="checkbox" checked={details.data.randomize_questions} onChange={(event) => details.setData('randomize_questions', event.target.checked)} /><span><strong>Randomize questions</strong><small>Stable once an attempt starts</small></span></label>
            <label className="prof-setting-check"><input disabled={!editable} type="checkbox" checked={details.data.randomize_choices} onChange={(event) => details.setData('randomize_choices', event.target.checked)} /><span><strong>Randomize choices</strong><small>Uses stable option identifiers</small></span></label>
          </div>
          {details.errors.schedule ? <p className="prof-validation-summary" role="alert">{details.errors.schedule}</p> : null}
          {editable ? (
            <div className="prof-form-actions">
              {quiz.authoring.mode === 'initial_draft' ? <button className="button button--danger-soft" type="button" onClick={removeQuiz}>Delete draft</button> : <span />}
              <button className="button button--primary" type="submit" disabled={details.processing}>{details.processing ? 'Saving…' : 'Save quiz details'}</button>
            </div>
          ) : null}
        </form>
      </section>

      <div className="prof-editor-layout prof-database-editor">
        <aside className="prof-question-list" aria-label="Quiz questions">
          <div><span>{quiz.questions.length} {quiz.questions.length === 1 ? 'question' : 'questions'}</span><strong>Stable UUIDs</strong></div>
          {quiz.questions.map((question) => (
            <button className={question.id === selectedId ? 'prof-question-item prof-question-item--active' : 'prof-question-item'} type="button" key={question.id} onClick={() => selectQuestion(question)} aria-current={question.id === selectedId ? 'true' : undefined}>
              <span>{question.position}</span>
              <span><strong>{question.prompt}</strong><small>{question.typeLabel} · {question.choices.length} choices</small></span>
              <Icon name="chevronRight" size={16} />
            </button>
          ))}
          {editable ? <button className="prof-ai-entry" type="button" onClick={startQuestion}><Icon name="plus" size={18} /><span><strong>Add a manual question</strong><small>MCQ or True/False</small></span></button> : null}
        </aside>

        <form className="prof-panel prof-question-editor" onSubmit={saveQuestion} noValidate>
          <div className="prof-panel-heading">
            <div><p className="section-kicker">{selected ? `Question ${selected.position}` : 'New question'}</p><h2>{editable ? (selected ? 'Edit question' : 'Question details') : 'Published question'}</h2></div>
            <StatusBadge tone={editable ? 'warning' : 'success'}>{editable ? 'Draft' : 'Immutable'}</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field"><span>Question type</span><select disabled={!editable} value={questionForm.data.type} onChange={(event) => changeType(event.target.value)}>{questionTypes.map((type) => <option value={type.value} key={type.value}>{type.label}</option>)}</select><FieldError message={questionForm.errors.type} /></label>
            <div className="prof-field prof-field--question-id"><span>Stable identifier</span><code>{selected?.id ?? 'Assigned when saved'}</code></div>
            <label className="prof-field prof-field--wide"><span>Prompt</span><textarea disabled={!editable} rows="4" value={questionForm.data.prompt} onChange={(event) => questionForm.setData('prompt', event.target.value)} aria-invalid={Boolean(questionForm.errors.prompt)} /><FieldError message={questionForm.errors.prompt} /></label>
          </div>

          <fieldset className="prof-choice-editor" disabled={!editable}>
            <legend>Answer choices and correct answer</legend>
            {questionForm.data.choices.map((choice, index) => (
              <label key={choice.id ?? `new-${index}`}>
                <input type="radio" name="correct-choice" checked={choice.is_correct} onChange={() => updateChoice(index, 'is_correct', true)} />
                <span>{String.fromCharCode(65 + index)}</span>
                <input aria-label={`Choice ${String.fromCharCode(65 + index)}`} value={choice.choice_text} onChange={(event) => updateChoice(index, 'choice_text', event.target.value)} readOnly={questionForm.data.type === 'true_false' || !editable} />
                {editable && questionForm.data.type === 'multiple_choice' && questionForm.data.choices.length > 2 ? <button className="prof-remove-choice" type="button" onClick={() => questionForm.setData('choices', questionForm.data.choices.filter((_, choiceIndex) => choiceIndex !== index))} aria-label={`Remove choice ${String.fromCharCode(65 + index)}`}><Icon name="close" size={16} /></button> : null}
              </label>
            ))}
          </fieldset>
          <FieldError message={[...new Set(choiceErrors)].join(' ')} />
          {editable && questionForm.data.type === 'multiple_choice' && questionForm.data.choices.length < 6 ? <button className="prof-text-button prof-add-choice" type="button" onClick={() => questionForm.setData('choices', [...questionForm.data.choices, { choice_text: '', is_correct: false }])}><Icon name="plus" size={16} /> Add choice</button> : null}

          <div className="prof-editor-footer">
            <small className="prof-question-safety">Answer keys are returned only inside this owner-authorized editor. Published records are immutable.</small>
            {editable ? <div>{selected ? <button className="button button--danger-soft" type="button" onClick={removeQuestion}>Remove</button> : null}<button className="button button--primary" type="submit" disabled={questionForm.processing}>{questionForm.processing ? 'Saving…' : selected ? 'Save question' : 'Add question'}</button></div> : null}
          </div>
        </form>
      </div>

      <section className="prof-panel prof-policy-locks" aria-labelledby="policy-locks-title">
        <div><Icon name="lock" size={20} /><div><p className="section-kicker">Still unavailable</p><h2 id="policy-locks-title">Attempt lifecycle and grading</h2></div></div>
        <p>Assessment Start, timers, saves, submission, scoring, and manual result release are implemented behind default-off operational gates. Roster mutation, administrator mutation, regrading, overrides, ranking, and exports remain unavailable.</p>
      </section>
    </ProfessorLayout>
  )
}
