import { router, usePage } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'
import Icon from '@prototype/components/Icon.jsx'
import AssessmentTimer from '../../../Components/AssessmentTimer.jsx'
import AttemptNavigation from '../../../Components/AttemptNavigation.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'
import useAuthoritativeCountdown from '../../../hooks/useAuthoritativeCountdown.js'

function TerminalAttempt({ attempt }) {
  const released = attempt.result.state === 'released'

  return (
    <StudentQuizLayout active="join" title="Submission receipt">
      <main className="assessment-shell" id="main-content">
        <section className="assessment-receipt panel" aria-labelledby="receipt-title">
          <span className="assessment-receipt__icon"><Icon name="checkCircle" size={30} /></span>
          <p className="section-kicker">Server-confirmed receipt</p>
          <h1 id="receipt-title">Assessment submitted</h1>
          <p>Your terminal receipt is stable. Refreshing or retrying cannot create another submission.</p>
          <dl>
            <div><dt>Quiz</dt><dd>{attempt.quiz.title}</dd></div>
            <div><dt>Receipt</dt><dd><code>{attempt.receipt.id}</code></dd></div>
            <div><dt>Finalized by</dt><dd>{attempt.receipt.reason.replaceAll('_', ' ')}</dd></div>
            <div><dt>Submitted</dt><dd>{new Date(attempt.receipt.submittedAt).toLocaleString('en-PH', { timeZone: 'Asia/Manila' })} Asia/Manila</dd></div>
          </dl>
        </section>

        <section className="assessment-result panel" aria-live="polite">
          <p className="section-kicker">Professor-controlled result</p>
          {released ? (
            <>
              <h2>{attempt.result.earnedPoints} / {attempt.result.maximumPoints} points</h2>
              <p>This is the complete approved student feedback. Correct answers and item-level correctness remain private.</p>
            </>
          ) : (
            <>
              <h2>{attempt.result.state === 'processing'
                ? 'Score processing'
                : attempt.result.state === 'processing_failed' ? 'Score unavailable' : 'Result withheld'}</h2>
              <p>{attempt.result.state === 'processing'
                ? 'Your submission is preserved. Release is unavailable until objective scoring completes.'
                : attempt.result.state === 'processing_failed'
                  ? 'Your submission receipt is preserved, but scoring needs authorized review before release.'
                  : 'Your Professor has not released this score. No grade information is included in this page.'}</p>
            </>
          )}
        </section>
      </main>
    </StudentQuizLayout>
  )
}

function ActiveAssessment({ attempt, errors, flash }) {
  const question = attempt.currentQuestion
  const [selectedChoiceId, setSelectedChoiceId] = useState(question?.selectedChoiceId ?? null)
  const [saveState, setSaveState] = useState(flash?.assessment?.state === 'saved' ? 'Saved' : question?.savedAt ? 'Saved' : 'Ready')
  const reconciled = useRef(false)
  const idempotencyKey = useRef(null)
  const attemptRemaining = useAuthoritativeCountdown(attempt.serverNow, attempt.timing.effectiveDeadlineAt)
  const questionRemaining = useAuthoritativeCountdown(attempt.serverNow, attempt.timing.questionDeadlineAt)

  useEffect(() => {
    const disconnected = () => setSaveState('Connection Lost')
    const reconnected = () => {
      setSaveState('Reconnecting')
      router.reload({ only: ['attempt'] })
    }
    window.addEventListener('offline', disconnected)
    window.addEventListener('online', reconnected)
    return () => {
      window.removeEventListener('offline', disconnected)
      window.removeEventListener('online', reconnected)
    }
  }, [])

  useEffect(() => {
    const reached = attemptRemaining === 0 || questionRemaining === 0
    if (attempt.status === 'in_progress' && reached && !reconciled.current) {
      reconciled.current = true
      router.reload({ only: ['attempt'], onFinish: () => { reconciled.current = false } })
    }
  }, [attempt.status, attemptRemaining, questionRemaining])

  const finalQuestion = question?.position === question?.total
  const everyQuestionAnswered = attempt.navigation.every((item) => item.answered)

  function saveAnswer(event) {
    event.preventDefault()
    if (!selectedChoiceId || !question) return
    setSaveState('Saving')
    idempotencyKey.current ??= `answer-${window.crypto.randomUUID()}`
    router.post(`/student/attempts/${attempt.id}/answers`, {
      question_snapshot_id: question.id,
      selected_choice_id: selectedChoiceId,
      expected_version: attempt.version,
      idempotency_key: idempotencyKey.current,
    }, {
      preserveScroll: true,
      onSuccess: () => setSaveState('Saved'),
      onError: () => setSaveState(window.navigator.onLine ? 'Save Failed' : 'Connection Lost'),
    })
  }

  function advance() {
    router.post(`/student/attempts/${attempt.id}/advance`, {
      expected_version: attempt.version,
    }, { preserveScroll: true })
  }

  function submit() {
    if (!everyQuestionAnswered) return
    if (!window.confirm('Submit this assessment now? This action is final.')) return
    router.post(`/student/attempts/${attempt.id}/submit`, {
      expected_version: attempt.version,
      confirmed: true,
    })
  }

  return (
    <StudentQuizLayout active="join" title="Assessment">
      <main className="assessment-shell" id="main-content">
        <header className="assessment-heading">
          <div>
            <p className="section-kicker">{attempt.quiz.courseCode} · Database-backed assessment</p>
            <h1>{attempt.quiz.title}</h1>
            <p>Server timestamps and acknowledged saves are authoritative. Times display in Asia/Manila.</p>
          </div>
          <div className="assessment-timers" aria-label="Assessment timers">
            <AssessmentTimer label="Quiz" remaining={attemptRemaining} />
            <AssessmentTimer label="Question" remaining={questionRemaining} />
          </div>
        </header>

        <AttemptNavigation items={attempt.navigation} />

        <form className="assessment-question panel" onSubmit={saveAnswer}>
          <div className="assessment-question__meta">
            <span>Question {question.position} of {question.total}</span>
            <strong className={`save-state save-state--${saveState.toLowerCase().replaceAll(' ', '-')}`} aria-live="polite">{saveState}</strong>
          </div>
          <h2>{question.prompt}</h2>
          <fieldset>
            <legend className="sr-only">Select one answer</legend>
            {question.choices.map((choice, index) => (
              <label className={selectedChoiceId === choice.id ? 'assessment-choice assessment-choice--selected' : 'assessment-choice'} key={choice.id}>
                <input type="radio" name="answer" value={choice.id} checked={selectedChoiceId === choice.id} onChange={() => {
                  setSelectedChoiceId(choice.id)
                  setSaveState('Ready')
                  idempotencyKey.current = null
                }} />
                <span>{String.fromCharCode(65 + index)}</span>
                <strong>{choice.text}</strong>
              </label>
            ))}
          </fieldset>
          {errors?.answer || errors?.progression || errors?.submission ? (
            <p className="assessment-error" role="alert">{errors.answer || errors.progression || errors.submission}</p>
          ) : null}
          <div className="assessment-actions">
            <button className="button button--secondary" type="submit" disabled={!selectedChoiceId || saveState === 'Saving'}>
              <Icon name="check" size={18} /> Save answer
            </button>
            {!finalQuestion ? (
              <button className="button button--primary" type="button" onClick={advance} disabled={saveState !== 'Saved'}>
                Next question <Icon name="arrowRight" size={18} />
              </button>
            ) : (
              <button className="button button--primary" type="button" onClick={submit} disabled={!everyQuestionAnswered || saveState !== 'Saved'}>
                Submit assessment <Icon name="send" size={18} />
              </button>
            )}
          </div>
          {finalQuestion && !everyQuestionAnswered ? <p className="assessment-policy-note">Voluntary submission with unanswered items remains disabled. Automatic deadline finalization will preserve acknowledged answers.</p> : null}
        </form>
      </main>
    </StudentQuizLayout>
  )
}

export default function Assessment({ attempt }) {
  const { errors, flash } = usePage().props

  if (attempt.status === 'submitted') return <TerminalAttempt attempt={attempt} />

  const viewKey = `${attempt.id}:${attempt.serverNow}:${attempt.currentQuestion?.id ?? 'none'}`
  return <ActiveAssessment key={viewKey} attempt={attempt} errors={errors} flash={flash} />
}
