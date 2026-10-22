import { Link, router, useForm } from '@inertiajs/react'
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
  if (!question) {
    return { type: 'multiple_choice', prompt: '', choices: emptyMcqChoices() }
  }

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

function BuilderSteps() {
  return (
    <nav className="prof-builder-steps" aria-label="Quiz builder steps">
      {['Identity', 'Questions', 'Rules', 'Preview', 'Publish'].map((label, index) => (
        <span className={index === 1 ? 'prof-builder-step prof-builder-step--active' : 'prof-builder-step prof-builder-step--locked'} key={label}>
          <span>{index + 1}</span>
          <strong>{label}</strong>
        </span>
      ))}
    </nav>
  )
}

export default function EditQuiz({ quiz, questionTypes }) {
  const [selectedId, setSelectedId] = useState(quiz.questions[0]?.id ?? null)
  const [copied, setCopied] = useState(false)
  const selected = quiz.questions.find((question) => question.id === selectedId) ?? null
  const details = useForm({
    title: quiz.title,
    course_code: quiz.courseCode,
    course_name: quiz.courseName ?? '',
    instructions: quiz.instructions ?? '',
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
      if (field === 'is_correct') {
        return { ...choice, is_correct: choiceIndex === index }
      }
      return choiceIndex === index ? { ...choice, [field]: value } : choice
    }))
  }

  function saveQuestion(event) {
    event.preventDefault()
    const options = { preserveScroll: true }

    if (selectedId) {
      questionForm.put(`/professor/quizzes/${quiz.id}/questions/${selectedId}`, options)
      return
    }

    questionForm.post(`/professor/quizzes/${quiz.id}/questions`, {
      ...options,
      onSuccess: () => {
        setSelectedId(null)
        questionForm.setData(questionData(null))
        questionForm.clearErrors()
      },
    })
  }

  function removeQuestion() {
    if (!selected || !window.confirm(`Remove question ${selected.position}? This cannot be undone.`)) return

    router.delete(`/professor/quizzes/${quiz.id}/questions/${selected.id}`, {
      preserveScroll: true,
      onSuccess: () => startQuestion(),
    })
  }

  function removeQuiz() {
    if (!window.confirm(`Delete the private draft “${quiz.title}”? This cannot be undone.`)) return
    router.delete(`/professor/quizzes/${quiz.id}`)
  }

  async function copyCode() {
    if (!window.navigator.clipboard) return
    await window.navigator.clipboard.writeText(quiz.access.code)
    setCopied(true)
    window.setTimeout(() => setCopied(false), 1800)
  }

  return (
    <ProfessorLayout title="Quiz builder">
      <PolicyBanner />
      <ScreenHeader
        eyebrow={`${quiz.courseCode} · ${quiz.statusLabel}`}
        title={quiz.title}
        description="Edit persisted quiz details and stable question records. Every write is validated and owner-authorized by Laravel."
        actions={(
          <Link className="button button--secondary" href="/professor/quizzes">
            <Icon name="arrowLeft" size={18} />
            All quizzes
          </Link>
        )}
      />
      <BuilderSteps />

      <section className="prof-panel prof-authoring-summary" aria-label="Draft status">
        <div>
          <span className="section-kicker">Reserved access code</span>
          <strong>{quiz.access.code}</strong>
        </div>
        <div>
          <span>Student route</span>
          <strong>{quiz.access.path}</strong>
        </div>
        <div>
          <span>Display timezone</span>
          <strong>{quiz.displayTimezone}</strong>
        </div>
        <StatusBadge tone="warning">Student access disabled</StatusBadge>
        <button className="button button--secondary button--compact" type="button" onClick={copyCode}>
          <Icon name={copied ? 'check' : 'copy'} size={16} />
          {copied ? 'Copied' : 'Copy code'}
        </button>
      </section>

      <section className="prof-panel prof-details-card" aria-labelledby="quiz-details-title">
        <div className="prof-panel-heading">
          <div>
            <p className="section-kicker">Draft record</p>
            <h2 id="quiz-details-title">Quiz details</h2>
          </div>
          <StatusBadge tone="warning">{quiz.statusLabel}</StatusBadge>
        </div>
        <form onSubmit={(event) => {
          event.preventDefault()
          details.put(`/professor/quizzes/${quiz.id}`, { preserveScroll: true })
        }}>
          <div className="prof-field-grid">
            <label className="prof-field prof-field--wide">
              <span>Quiz title</span>
              <input value={details.data.title} onChange={(event) => details.setData('title', event.target.value)} aria-invalid={Boolean(details.errors.title)} />
              <FieldError message={details.errors.title} />
            </label>
            <label className="prof-field">
              <span>Course code</span>
              <input value={details.data.course_code} onChange={(event) => details.setData('course_code', event.target.value)} aria-invalid={Boolean(details.errors.course_code)} />
              <FieldError message={details.errors.course_code} />
            </label>
            <label className="prof-field">
              <span>Course name <small>(optional)</small></span>
              <input value={details.data.course_name} onChange={(event) => details.setData('course_name', event.target.value)} aria-invalid={Boolean(details.errors.course_name)} />
              <FieldError message={details.errors.course_name} />
            </label>
            <label className="prof-field prof-field--wide">
              <span>Student-facing instructions <small>(optional)</small></span>
              <textarea rows="3" value={details.data.instructions} onChange={(event) => details.setData('instructions', event.target.value)} aria-invalid={Boolean(details.errors.instructions)} />
              <FieldError message={details.errors.instructions} />
            </label>
          </div>
          <div className="prof-form-actions">
            <button className="button button--danger-soft" type="button" onClick={removeQuiz}>Delete draft</button>
            <button className="button button--primary" type="submit" disabled={details.processing}>
              {details.processing ? 'Saving…' : 'Save quiz details'}
            </button>
          </div>
        </form>
      </section>

      <div className="prof-editor-layout prof-database-editor">
        <aside className="prof-question-list" aria-label="Quiz questions">
          <div>
            <span>{quiz.questions.length} {quiz.questions.length === 1 ? 'question' : 'questions'}</span>
            <strong>Stable UUIDs</strong>
          </div>
          {quiz.questions.map((question) => (
            <button
              className={question.id === selectedId ? 'prof-question-item prof-question-item--active' : 'prof-question-item'}
              type="button"
              key={question.id}
              onClick={() => selectQuestion(question)}
              aria-current={question.id === selectedId ? 'true' : undefined}
            >
              <span>{question.position}</span>
              <span>
                <strong>{question.prompt}</strong>
                <small>{question.typeLabel} · {question.choices.length} choices</small>
              </span>
              <Icon name="chevronRight" size={16} />
            </button>
          ))}
          <button className="prof-ai-entry" type="button" onClick={startQuestion}>
            <Icon name="plus" size={18} />
            <span>
              <strong>Add a manual question</strong>
              <small>MCQ or True/False</small>
            </span>
          </button>
        </aside>

        <form className="prof-panel prof-question-editor" onSubmit={saveQuestion} noValidate>
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">{selected ? `Question ${selected.position}` : 'New question'}</p>
              <h2>{selected ? 'Edit question' : 'Question details'}</h2>
            </div>
            <StatusBadge tone="success">Manually authored</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field">
              <span>Question type</span>
              <select value={questionForm.data.type} onChange={(event) => changeType(event.target.value)}>
                {questionTypes.map((type) => <option value={type.value} key={type.value}>{type.label}</option>)}
              </select>
              <FieldError message={questionForm.errors.type} />
            </label>
            <div className="prof-field prof-field--question-id">
              <span>Stable identifier</span>
              <code>{selected?.id ?? 'Assigned when saved'}</code>
            </div>
            <label className="prof-field prof-field--wide">
              <span>Prompt</span>
              <textarea rows="4" value={questionForm.data.prompt} onChange={(event) => questionForm.setData('prompt', event.target.value)} aria-invalid={Boolean(questionForm.errors.prompt)} />
              <FieldError message={questionForm.errors.prompt} />
            </label>
          </div>

          <fieldset className="prof-choice-editor">
            <legend>Answer choices and correct answer</legend>
            {questionForm.data.choices.map((choice, index) => (
              <label key={choice.id ?? `new-${index}`}>
                <input
                  type="radio"
                  name="correct-choice"
                  checked={choice.is_correct}
                  onChange={() => updateChoice(index, 'is_correct', true)}
                />
                <span>{String.fromCharCode(65 + index)}</span>
                <input
                  aria-label={`Choice ${String.fromCharCode(65 + index)}`}
                  value={choice.choice_text}
                  onChange={(event) => updateChoice(index, 'choice_text', event.target.value)}
                  readOnly={questionForm.data.type === 'true_false'}
                />
                {questionForm.data.type === 'multiple_choice' && questionForm.data.choices.length > 2 ? (
                  <button
                    className="prof-remove-choice"
                    type="button"
                    onClick={() => questionForm.setData('choices', questionForm.data.choices.filter((_, choiceIndex) => choiceIndex !== index))}
                    aria-label={`Remove choice ${String.fromCharCode(65 + index)}`}
                  >
                    <Icon name="close" size={16} />
                  </button>
                ) : null}
              </label>
            ))}
          </fieldset>
          <FieldError message={[...new Set(choiceErrors)].join(' ')} />
          {questionForm.data.type === 'multiple_choice' && questionForm.data.choices.length < 6 ? (
            <button
              className="prof-text-button prof-add-choice"
              type="button"
              onClick={() => questionForm.setData('choices', [...questionForm.data.choices, { choice_text: '', is_correct: false }])}
            >
              <Icon name="plus" size={16} /> Add choice
            </button>
          ) : null}

          <div className="prof-editor-footer">
            <small className="prof-question-safety">Exactly one correct answer is required. Answer keys are only returned inside this owner-authorized editor.</small>
            <div>
              {selected ? (
                <button className="button button--danger-soft" type="button" onClick={removeQuestion}>Remove</button>
              ) : null}
              <button className="button button--primary" type="submit" disabled={questionForm.processing}>
                {questionForm.processing ? 'Saving…' : selected ? 'Save question' : 'Add question'}
              </button>
            </div>
          </div>
        </form>
      </div>

      <section className="prof-panel prof-policy-locks" aria-labelledby="policy-locks-title">
        <div>
          <Icon name="lock" size={20} />
          <div>
            <p className="section-kicker">Deliberately unavailable</p>
            <h2 id="policy-locks-title">Rules, preview, and publishing</h2>
          </div>
        </div>
        <p>Deadline, expiration, timer, scoring, eligibility, publishing, and student-attempt behavior require the remaining D04–D12 decisions. No public access route has been registered.</p>
      </section>
    </ProfessorLayout>
  )
}
