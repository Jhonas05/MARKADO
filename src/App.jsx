import { useEffect, useMemo, useState } from 'react'
import AdminApp from './AdminApp.jsx'
import BrandMark from './components/BrandMark.jsx'
import Icon from './components/Icon.jsx'
import ThemeToggle from './components/ThemeToggle.jsx'
import ProfessorApp from './ProfessorApp.jsx'
import {
  questions,
  quiz,
  recentResults,
  student,
} from './data/studentPrototype.js'

const screens = {
  dashboard: 'dashboard',
  join: 'join',
  instructions: 'instructions',
  quiz: 'quiz',
  review: 'review',
  results: 'results',
}

function formatTime(totalSeconds) {
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
}

function AppHeader({
  currentScreen,
  onHelp,
  onNavigate,
  onSwitchAdmin,
  onSwitchExperience,
  submitted,
}) {
  const isDashboard = currentScreen === screens.dashboard
  const isAssessment = [
    screens.instructions,
    screens.quiz,
    screens.review,
  ].includes(currentScreen)

  return (
    <header className="app-header">
      <div className="app-header__inner">
        <button
          className="brand"
          type="button"
          onClick={() => onNavigate(screens.dashboard)}
          aria-label="MARKADO dashboard"
        >
          <BrandMark />
          <span className="brand__name">MARKADO</span>
        </button>

        <nav className="primary-nav" aria-label="Primary navigation">
          <button
            className={isDashboard ? 'nav-link nav-link--active' : 'nav-link'}
            type="button"
            onClick={() => onNavigate(screens.dashboard)}
            aria-current={isDashboard ? 'page' : undefined}
          >
            <Icon name="home" size={18} />
            <span>Overview</span>
          </button>
          <button
            className={isAssessment ? 'nav-link nav-link--active' : 'nav-link'}
            type="button"
            onClick={() => onNavigate(screens.join)}
            aria-current={isAssessment ? 'page' : undefined}
          >
            <Icon name="book" size={18} />
            <span>My quizzes</span>
          </button>
          <button
            className={
              currentScreen === screens.results
                ? 'nav-link nav-link--active'
                : 'nav-link'
            }
            type="button"
            onClick={() =>
              onNavigate(submitted ? screens.results : screens.dashboard)
            }
            aria-current={
              currentScreen === screens.results ? 'page' : undefined
            }
          >
            <Icon name="medal" size={18} />
            <span>Results</span>
          </button>
        </nav>

        <div className="header-actions">
          <ThemeToggle compact />
          <button
            className="student-experience-switch"
            type="button"
            onClick={onSwitchExperience}
          >
            <Icon name="dashboard" size={17} />
            <span>Professor prototype</span>
          </button>
          <button
            className="student-experience-switch"
            type="button"
            onClick={onSwitchAdmin}
          >
            <Icon name="shield" size={17} />
            <span>Administrator prototype</span>
          </button>
          <button
            className="icon-button"
            type="button"
            onClick={onHelp}
            aria-label="Open help and guide"
          >
            <Icon name="help" />
          </button>
          <div className="student-chip" aria-label={`Signed in as ${student.name}`}>
            <span className="student-chip__avatar">{student.initials}</span>
            <span className="student-chip__copy">
              <strong>{student.name}</strong>
              <small>{student.studentNumber}</small>
            </span>
          </div>
        </div>
      </div>
    </header>
  )
}

function PrototypeNotice() {
  return (
    <div className="prototype-notice" role="note">
      <Icon name="spark" size={17} />
      <p>
        <strong>Interactive UI prototype.</strong> Uses synthetic data; no real
        account, server save, official timer, or grade is created.
      </p>
    </div>
  )
}

function Dashboard({ onJoin, onOpenInstructions }) {
  return (
    <main className="page-shell dashboard-page" id="main-content">
      <PrototypeNotice />

      <section className="dashboard-intro">
        <div>
          <p className="section-kicker">Student overview</p>
          <h1>Welcome back, {student.firstName}.</h1>
          <p className="intro-copy">
            One practice assessment is ready. Review the rules before you begin.
          </p>
        </div>
        <button className="button button--dark" type="button" onClick={onJoin}>
          Join with a code
          <Icon name="arrowRight" size={18} />
        </button>
      </section>

      <section className="metric-grid" aria-label="Assessment summary">
        <article className="metric-card metric-card--accent">
          <span className="metric-card__icon">
            <Icon name="book" />
          </span>
          <div>
            <strong>1</strong>
            <span>Ready to take</span>
          </div>
          <small>This week</small>
        </article>
        <article className="metric-card">
          <span className="metric-card__icon metric-card__icon--green">
            <Icon name="checkCircle" />
          </span>
          <div>
            <strong>3</strong>
            <span>Completed</span>
          </div>
          <small>October</small>
        </article>
        <article className="metric-card">
          <span className="metric-card__icon metric-card__icon--blue">
            <Icon name="trophy" />
          </span>
          <div>
            <strong>88%</strong>
            <span>Average score</span>
          </div>
          <small>Practice data</small>
        </article>
      </section>

      <div className="dashboard-grid">
        <section className="panel panel--featured" aria-labelledby="ready-title">
          <div className="panel-heading">
            <div>
              <p className="section-kicker">Ready now</p>
              <h2 id="ready-title">Your next quiz</h2>
            </div>
            <span className="status status--open">Practice</span>
          </div>

          <div className="quiz-feature">
            <div className="quiz-feature__subject" aria-hidden="true">
              <span>BIO</span>
              <strong>104</strong>
            </div>
            <div className="quiz-feature__body">
              <p className="quiz-feature__course">BIO 104 · General Biology</p>
              <h3>{quiz.subtitle}</h3>
              <p className="muted">Prepared by {quiz.instructor}</p>

              <dl className="quiz-meta">
                <div>
                  <dt>
                    <Icon name="calendar" size={17} />
                    Schedule
                  </dt>
                  <dd>{quiz.schedule}</dd>
                </div>
                <div>
                  <dt>
                    <Icon name="clock" size={17} />
                    Duration
                  </dt>
                  <dd>{quiz.durationMinutes} minutes</dd>
                </div>
                <div>
                  <dt>
                    <Icon name="book" size={17} />
                    Questions
                  </dt>
                  <dd>{questions.length} items</dd>
                </div>
              </dl>

              <button
                className="button button--primary"
                type="button"
                onClick={onOpenInstructions}
              >
                View instructions
                <Icon name="arrowRight" size={18} />
              </button>
            </div>
          </div>
        </section>

        <section className="panel activity-panel" aria-labelledby="activity-title">
          <div className="panel-heading">
            <div>
              <p className="section-kicker">Recent work</p>
              <h2 id="activity-title">Practice results</h2>
            </div>
            <span className="text-link">Synthetic</span>
          </div>
          <div className="result-list">
            {recentResults.map((result) => (
              <article className="result-row" key={result.subject}>
                <div className="result-row__topline">
                  <div>
                    <strong>{result.subject}</strong>
                    <span>{result.detail}</span>
                  </div>
                  <time>{result.date}</time>
                </div>
                <div className="score-line">
                  <div className="score-track" aria-hidden="true">
                    <span style={{ width: `${result.percent}%` }} />
                  </div>
                  <strong>{result.score}</strong>
                </div>
              </article>
            ))}
          </div>
        </section>
      </div>
    </main>
  )
}

function JoinQuiz({ onBack, onFound }) {
  const [code, setCode] = useState('')
  const [error, setError] = useState('')

  function handleSubmit(event) {
    event.preventDefault()
    const normalized = code.trim().toUpperCase()

    if (normalized !== quiz.code) {
      setError(
        "We couldn't find an open quiz with that code. Check the code with your professor.",
      )
      return
    }

    setError('')
    onFound()
  }

  return (
    <main className="page-shell flow-page" id="main-content">
      <button className="back-link" type="button" onClick={onBack}>
        <Icon name="arrowLeft" size={18} />
        Back to overview
      </button>

      <div className="flow-layout">
        <section className="flow-copy" aria-labelledby="join-title">
          <p className="section-kicker">Quiz access</p>
          <h1 id="join-title">Enter your quiz code.</h1>
          <p>
            Codes help you find an assessment. In the production system, your
            account, schedule, and eligibility will still be checked by the
            server before an attempt can begin.
          </p>
          <div className="trust-note">
            <Icon name="shield" />
            <div>
              <strong>Access stays protected</strong>
              <span>A quiz code is not treated as proof of permission.</span>
            </div>
          </div>
        </section>

        <section className="join-card" aria-label="Join a quiz">
          <div className="join-card__icon">
            <Icon name="search" size={26} />
          </div>
          <h2>Find your assessment</h2>
          <p className="muted">Ask your instructor for the six-character code.</p>

          <form onSubmit={handleSubmit} noValidate>
            <label htmlFor="quiz-code">Quiz code</label>
            <input
              id="quiz-code"
              className={error ? 'code-input code-input--error' : 'code-input'}
              value={code}
              onChange={(event) => {
                setCode(event.target.value.toUpperCase())
                setError('')
              }}
              aria-describedby={error ? 'code-error code-hint' : 'code-hint'}
              aria-invalid={Boolean(error)}
              placeholder="ABC-123"
              maxLength={7}
              autoComplete="off"
              autoFocus
            />
            <div className="input-meta">
              <span id="code-hint">
                Prototype code: <strong>{quiz.code}</strong>
              </span>
              <span>{code.length}/7</span>
            </div>
            {error ? (
              <p className="form-error" id="code-error" role="alert">
                {error}
              </p>
            ) : null}
            <button className="button button--primary button--full" type="submit">
              Find quiz
              <Icon name="arrowRight" size={18} />
            </button>
          </form>
        </section>
      </div>
    </main>
  )
}

function Instructions({ onBack, onStart }) {
  const [ready, setReady] = useState(false)

  return (
    <main className="page-shell instructions-page" id="main-content">
      <button className="back-link" type="button" onClick={onBack}>
        <Icon name="arrowLeft" size={18} />
        Back
      </button>

      <div className="assessment-heading">
        <div>
          <span className="course-label">BIO 104 · {quiz.title}</span>
          <h1>Before you begin</h1>
          <p>{quiz.subtitle}</p>
        </div>
        <span className="status status--open">Practice prototype</span>
      </div>

      <div className="instruction-grid">
        <section className="panel instruction-main" aria-labelledby="rules-title">
          <div className="instruction-summary">
            <div>
              <Icon name="clock" />
              <span>
                <strong>{quiz.durationMinutes} min</strong>
                Prototype timer
              </span>
            </div>
            <div>
              <Icon name="book" />
              <span>
                <strong>{questions.length} items</strong>
                Multiple choice
              </span>
            </div>
            <div>
              <Icon name="flag" />
              <span>
                <strong>{quiz.points} points</strong>
                Practice score
              </span>
            </div>
          </div>

          <div className="rules-block">
            <h2 id="rules-title">What to expect</h2>
            <ol className="rules-list">
              <li>
                <span>01</span>
                <div>
                  <strong>Read each item carefully</strong>
                  <p>Select one answer for every question in this prototype.</p>
                </div>
              </li>
              <li>
                <span>02</span>
                <div>
                  <strong>Watch the visible timer</strong>
                  <p>
                    It keeps counting during review. Reaching zero does not
                    simulate an academic expiry policy.
                  </p>
                </div>
              </li>
              <li>
                <span>03</span>
                <div>
                  <strong>Review before you submit</strong>
                  <p>You can return to any item and see which ones are unanswered.</p>
                </div>
              </li>
              <li>
                <span>04</span>
                <div>
                  <strong>Wait for confirmation</strong>
                  <p>
                    Production submissions will require a server receipt. This
                    prototype produces a local preview only.
                  </p>
                </div>
              </li>
            </ol>
          </div>
        </section>

        <aside className="instruction-aside">
          <section className="schedule-card" aria-labelledby="schedule-title">
            <p className="section-kicker">Schedule</p>
            <h2 id="schedule-title">Available today</h2>
            <dl>
              <div>
                <dt>Date and time</dt>
                <dd>{quiz.schedule}</dd>
              </div>
              <div>
                <dt>Timezone</dt>
                <dd>{quiz.timezone}</dd>
              </div>
              <div>
                <dt>Instructor</dt>
                <dd>{quiz.instructor}</dd>
              </div>
            </dl>
          </section>

          <section className="start-card">
            <label className="confirmation-check">
              <input
                type="checkbox"
                checked={ready}
                onChange={(event) => setReady(event.target.checked)}
              />
              <span aria-hidden="true">
                <Icon name="check" size={16} />
              </span>
              <strong>I read the prototype instructions.</strong>
            </label>
            <p>
              Starting here creates no official attempt and sends no student data.
            </p>
            <button
              className="button button--primary button--full"
              type="button"
              disabled={!ready}
              onClick={onStart}
            >
              Start practice attempt
              <Icon name="arrowRight" size={18} />
            </button>
          </section>
        </aside>
      </div>
    </main>
  )
}

function QuizRunner({
  answers,
  currentQuestion,
  onAnswer,
  onCurrentQuestion,
  onReview,
  secondsRemaining,
}) {
  const question = questions[currentQuestion]
  const answeredCount = Object.keys(answers).length

  return (
    <main className="quiz-page" id="main-content">
      <div className="quiz-toolbar">
        <div>
          <span className="course-label">BIO 104</span>
          <strong>{quiz.subtitle}</strong>
        </div>
        <div
          className={
            secondsRemaining <= 300 ? 'timer timer--warning' : 'timer'
          }
          aria-label={`${formatTime(secondsRemaining)} remaining on the prototype timer`}
        >
          <Icon name="clock" />
          <span>
            <small>Time remaining</small>
            <strong>{formatTime(secondsRemaining)}</strong>
          </span>
        </div>
      </div>

      {secondsRemaining === 0 ? (
        <div className="timer-expired" role="status">
          Prototype timer reached zero. No expiry or auto-submit policy is
          simulated.
        </div>
      ) : null}

      <div className="quiz-layout">
        <aside className="question-sidebar" aria-label="Question navigator">
          <div className="question-sidebar__heading">
            <div>
              <p className="section-kicker">Progress</p>
              <strong>
                {answeredCount} of {questions.length} answered
              </strong>
            </div>
            <span>{Math.round((answeredCount / questions.length) * 100)}%</span>
          </div>
          <div className="progress-track" aria-hidden="true">
            <span
              style={{ width: `${(answeredCount / questions.length) * 100}%` }}
            />
          </div>
          <div className="question-map">
            {questions.map((item, index) => {
              const isCurrent = index === currentQuestion
              const isAnswered = Boolean(answers[item.id])
              return (
                <button
                  className={[
                    'question-map__item',
                    isCurrent ? 'question-map__item--current' : '',
                    isAnswered ? 'question-map__item--answered' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  type="button"
                  key={item.id}
                  onClick={() => onCurrentQuestion(index)}
                  aria-current={isCurrent ? 'step' : undefined}
                  aria-label={`Question ${index + 1}${isAnswered ? ', answered' : ', unanswered'}`}
                >
                  <span>{index + 1}</span>
                  <span className="question-map__copy">
                    <strong>Question {index + 1}</strong>
                    <small>{isAnswered ? 'Answered' : 'Not answered'}</small>
                  </span>
                  {isAnswered ? <Icon name="check" size={16} /> : null}
                </button>
              )
            })}
          </div>
          <button
            className="button button--soft button--full"
            type="button"
            onClick={onReview}
          >
            Review answers
            <Icon name="arrowRight" size={18} />
          </button>
        </aside>

        <section className="question-stage" aria-labelledby="question-title">
          <div className="question-stage__meta">
            <span>
              Question {currentQuestion + 1} of {questions.length}
            </span>
            <span>{question.topic}</span>
          </div>
          <h1 id="question-title">{question.prompt}</h1>
          <p className="question-hint">Choose the best answer.</p>

          <fieldset className="choice-list">
            <legend className="sr-only">Answer choices</legend>
            {question.choices.map((choice, choiceIndex) => {
              const selected = answers[question.id] === choice.id
              return (
                <label
                  className={selected ? 'choice choice--selected' : 'choice'}
                  key={choice.id}
                >
                  <input
                    type="radio"
                    name={question.id}
                    value={choice.id}
                    checked={selected}
                    onChange={() => onAnswer(question.id, choice.id)}
                  />
                  <span className="choice__letter" aria-hidden="true">
                    {String.fromCharCode(65 + choiceIndex)}
                  </span>
                  <span className="choice__text">{choice.text}</span>
                  <span className="choice__check" aria-hidden="true">
                    <Icon name="check" size={17} />
                  </span>
                </label>
              )
            })}
          </fieldset>

          <div className="save-state" role="status" aria-live="polite">
            <Icon name="shield" size={17} />
            {answers[question.id]
              ? 'Response held in this prototype screen — not saved to a server.'
              : 'No response selected for this question.'}
          </div>

          <div className="question-actions">
            <button
              className="button button--secondary"
              type="button"
              disabled={currentQuestion === 0}
              onClick={() => onCurrentQuestion(currentQuestion - 1)}
            >
              <Icon name="arrowLeft" size={18} />
              Previous
            </button>
            {currentQuestion < questions.length - 1 ? (
              <button
                className="button button--primary"
                type="button"
                onClick={() => onCurrentQuestion(currentQuestion + 1)}
              >
                Next question
                <Icon name="arrowRight" size={18} />
              </button>
            ) : (
              <button
                className="button button--primary"
                type="button"
                onClick={onReview}
              >
                Review answers
                <Icon name="arrowRight" size={18} />
              </button>
            )}
          </div>
        </section>
      </div>
    </main>
  )
}

function ReviewAnswers({ answers, onBack, onEdit, onSubmit, secondsRemaining }) {
  const answeredCount = Object.keys(answers).length
  const unansweredCount = questions.length - answeredCount

  return (
    <main className="page-shell review-page" id="main-content">
      <button className="back-link" type="button" onClick={onBack}>
        <Icon name="arrowLeft" size={18} />
        Return to current question
      </button>

      <div className="review-heading">
        <div>
          <p className="section-kicker">Final check</p>
          <h1>Review your answers</h1>
          <p>Open any question to make a change before prototype submission.</p>
        </div>
        <div className="review-timer">
          <Icon name="clock" />
          <span>
            <small>Timer continues</small>
            <strong>{formatTime(secondsRemaining)}</strong>
          </span>
        </div>
      </div>

      {unansweredCount ? (
        <div className="review-alert" role="status">
          <Icon name="flag" />
          <div>
            <strong>
              {unansweredCount} {unansweredCount === 1 ? 'question is' : 'questions are'}{' '}
              unanswered.
            </strong>
            <span>You may still submit this prototype, or go back and answer.</span>
          </div>
        </div>
      ) : (
        <div className="review-alert review-alert--success" role="status">
          <Icon name="checkCircle" />
          <div>
            <strong>Every question has an answer.</strong>
            <span>You can still review and change any selection.</span>
          </div>
        </div>
      )}

      <section className="review-list" aria-label="Question response summary">
        {questions.map((question, index) => {
          const selectedChoice = question.choices.find(
            (choice) => choice.id === answers[question.id],
          )
          return (
            <button
              className="review-item"
              type="button"
              key={question.id}
              onClick={() => onEdit(index)}
            >
              <span
                className={
                  selectedChoice
                    ? 'review-item__number review-item__number--answered'
                    : 'review-item__number'
                }
              >
                {selectedChoice ? <Icon name="check" size={17} /> : index + 1}
              </span>
              <span className="review-item__copy">
                <small>Question {index + 1}</small>
                <strong>{question.prompt}</strong>
                <span>
                  {selectedChoice ? selectedChoice.text : 'No answer selected'}
                </span>
              </span>
              <span className="review-item__action">
                Edit
                <Icon name="chevronRight" size={17} />
              </span>
            </button>
          )
        })}
      </section>

      <section className="submit-bar" aria-label="Submit prototype answers">
        <div>
          <strong>
            {answeredCount}/{questions.length} answered
          </strong>
          <span>This action produces a local result preview only.</span>
        </div>
        <button className="button button--primary" type="button" onClick={onSubmit}>
          Submit prototype
          <Icon name="arrowRight" size={18} />
        </button>
      </section>
    </main>
  )
}

function SubmitDialog({ onCancel, onConfirm, unansweredCount }) {
  return (
    <div className="modal-layer" role="presentation">
      <button
        className="modal-backdrop"
        type="button"
        onClick={onCancel}
        aria-label="Close submission confirmation"
      />
      <section
        className="confirm-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-title"
        aria-describedby="confirm-copy"
      >
        <div className="confirm-dialog__icon">
          <Icon name="flag" size={25} />
        </div>
        <p className="section-kicker">Final action</p>
        <h2 id="confirm-title">Submit these answers?</h2>
        <p id="confirm-copy">
          {unansweredCount
            ? `${unansweredCount} unanswered ${unansweredCount === 1 ? 'question' : 'questions'} will be included as blank.`
            : 'All five questions have a response.'}{' '}
          This prototype will calculate a local preview score.
        </p>
        <div className="dialog-actions">
          <button className="button button--secondary" type="button" onClick={onCancel}>
            Keep reviewing
          </button>
          <button className="button button--primary" type="button" onClick={onConfirm} autoFocus>
            Confirm submission
          </button>
        </div>
      </section>
    </div>
  )
}

function ResultsPreview({ answers, onDashboard, onReview }) {
  const score = useMemo(
    () =>
      questions.reduce(
        (total, question) =>
          total + (answers[question.id] === question.correctChoiceId ? 1 : 0),
        0,
      ),
    [answers],
  )
  const percent = Math.round((score / questions.length) * 100)

  return (
    <main className="page-shell results-page" id="main-content">
      <section className="receipt-card" aria-labelledby="receipt-title">
        <div className="receipt-card__check">
          <Icon name="check" size={30} strokeWidth={2.2} />
        </div>
        <p className="section-kicker">Prototype complete</p>
        <h1 id="receipt-title">Submission received</h1>
        <p>
          This confirmation is part of the UI prototype. No official attempt or
          student record was created.
        </p>
        <div className="receipt-reference">
          <span>Prototype reference</span>
          <strong>MKD-DEMO-104</strong>
        </div>
      </section>

      <div className="results-grid">
        <section className="score-card" aria-labelledby="score-title">
          <div className="score-ring" aria-label={`${percent} percent`}>
            <strong>{percent}%</strong>
            <span>
              {score} of {questions.length}
            </span>
          </div>
          <div>
            <p className="section-kicker">Result preview</p>
            <h2 id="score-title">
              {percent >= 80 ? 'Strong work.' : 'Keep building your understanding.'}
            </h2>
            <p>
              Your local selections were compared with the prototype answer key.
              Production release and feedback rules remain undecided.
            </p>
          </div>
        </section>

        <section className="panel result-detail" aria-labelledby="detail-title">
          <div className="panel-heading">
            <div>
              <p className="section-kicker">Breakdown</p>
              <h2 id="detail-title">Question review</h2>
            </div>
            <span className="status status--neutral">Local preview</span>
          </div>
          <div className="breakdown-list">
            {questions.map((question, index) => {
              const isCorrect = answers[question.id] === question.correctChoiceId
              const wasAnswered = Boolean(answers[question.id])
              return (
                <div className="breakdown-row" key={question.id}>
                  <span
                    className={
                      isCorrect
                        ? 'breakdown-row__icon breakdown-row__icon--correct'
                        : 'breakdown-row__icon'
                    }
                  >
                    {isCorrect ? <Icon name="check" size={16} /> : index + 1}
                  </span>
                  <div>
                    <strong>Question {index + 1}</strong>
                    <span>{question.topic}</span>
                  </div>
                  <strong className={isCorrect ? 'correct-copy' : 'review-copy'}>
                    {isCorrect ? 'Correct' : wasAnswered ? 'Review' : 'Unanswered'}
                  </strong>
                </div>
              )
            })}
          </div>
          <button className="text-button" type="button" onClick={onReview}>
            Review explanations
            <Icon name="arrowRight" size={17} />
          </button>
        </section>
      </div>

      <div className="results-actions">
        <button className="button button--dark" type="button" onClick={onDashboard}>
          Return to overview
          <Icon name="arrowRight" size={18} />
        </button>
      </div>
    </main>
  )
}

function ExplanationReview({ answers, onClose }) {
  return (
    <main className="page-shell explanation-page" id="main-content">
      <button className="back-link" type="button" onClick={onClose}>
        <Icon name="arrowLeft" size={18} />
        Back to result preview
      </button>
      <div className="review-heading">
        <div>
          <p className="section-kicker">Learning review</p>
          <h1>Answer explanations</h1>
          <p>Synthetic feedback for this practice prototype.</p>
        </div>
      </div>
      <section className="explanation-list">
        {questions.map((question, index) => {
          const selected = question.choices.find(
            (choice) => choice.id === answers[question.id],
          )
          const correct = question.choices.find(
            (choice) => choice.id === question.correctChoiceId,
          )
          const isCorrect = selected?.id === question.correctChoiceId
          return (
            <article className="explanation-card" key={question.id}>
              <div className="explanation-card__heading">
                <span>{String(index + 1).padStart(2, '0')}</span>
                <div>
                  <small>{question.topic}</small>
                  <h2>{question.prompt}</h2>
                </div>
                <strong className={isCorrect ? 'correct-copy' : 'review-copy'}>
                  {isCorrect ? 'Correct' : 'Review'}
                </strong>
              </div>
              <dl>
                <div>
                  <dt>Your answer</dt>
                  <dd>{selected?.text ?? 'No answer selected'}</dd>
                </div>
                {!isCorrect ? (
                  <div>
                    <dt>Correct answer</dt>
                    <dd>{correct.text}</dd>
                  </div>
                ) : null}
              </dl>
              <p>{question.explanation}</p>
            </article>
          )
        })}
      </section>
    </main>
  )
}

function HelpPanel({ attemptActive, onClose }) {
  return (
    <div className="help-layer" role="presentation">
      <button
        className="help-backdrop"
        type="button"
        onClick={onClose}
        aria-label="Close help and guide"
      />
      <aside
        className="help-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="help-title"
      >
        <div className="help-panel__header">
          <div>
            <p className="section-kicker">Help & guide</p>
            <h2 id="help-title">What do you need?</h2>
          </div>
          <button
            className="icon-button"
            type="button"
            onClick={onClose}
            aria-label="Close help and guide"
            autoFocus
          >
            <Icon name="close" />
          </button>
        </div>

        {attemptActive ? (
          <div className="help-timer-note">
            <Icon name="clock" />
            <span>
              <strong>The prototype timer is still running.</strong>
              Help does not pause the current practice attempt.
            </span>
          </div>
        ) : null}

        <div className="help-topics">
          <details open>
            <summary>Joining a quiz</summary>
            <p>
              Enter the code supplied by your instructor. In production, a valid
              code will not bypass sign-in, schedule, or eligibility checks.
            </p>
          </details>
          <details>
            <summary>Answer and save status</summary>
            <p>
              Only a server acknowledgement may be labelled saved in production.
              This prototype keeps choices in the current browser screen only.
            </p>
          </details>
          <details>
            <summary>Reviewing and submitting</summary>
            <p>
              Use the question navigator to revisit any item. Final submission is
              separate from moving to the next question and requires confirmation.
            </p>
          </details>
          <details>
            <summary>Timer or connection issue</summary>
            <p>
              The production design will use a server-authoritative clock and show
              connection failures honestly. Outage and expiry policies still need
              academic approval.
            </p>
          </details>
        </div>

        <div className="help-footer">
          <Icon name="user" />
          <div>
            <strong>Still need help?</strong>
            <span>Contact your instructor before beginning an official attempt.</span>
          </div>
        </div>
      </aside>
    </div>
  )
}

function StudentBottomNav({ currentScreen, onHelp, onNavigate, submitted }) {
  const isQuizArea = [
    screens.join,
    screens.instructions,
  ].includes(currentScreen)

  return (
    <nav className="student-bottom-nav" aria-label="Mobile navigation">
      <button
        className={currentScreen === screens.dashboard ? 'is-active' : ''}
        type="button"
        onClick={() => onNavigate(screens.dashboard)}
        aria-current={currentScreen === screens.dashboard ? 'page' : undefined}
      >
        <Icon name="home" size={20} />
        <span>Overview</span>
      </button>
      <button
        className={isQuizArea ? 'is-active' : ''}
        type="button"
        onClick={() => onNavigate(screens.join)}
        aria-current={isQuizArea ? 'page' : undefined}
      >
        <Icon name="book" size={20} />
        <span>Quizzes</span>
      </button>
      <button
        className={currentScreen === screens.results ? 'is-active' : ''}
        type="button"
        onClick={() => onNavigate(submitted ? screens.results : screens.dashboard)}
        aria-current={currentScreen === screens.results ? 'page' : undefined}
      >
        <Icon name="medal" size={20} />
        <span>Results</span>
      </button>
      <button type="button" onClick={onHelp}>
        <Icon name="help" size={20} />
        <span>Help</span>
      </button>
    </nav>
  )
}

function StudentPrototype({ onSwitchAdmin, onSwitchExperience }) {
  const [screen, setScreen] = useState(screens.dashboard)
  const [answers, setAnswers] = useState({})
  const [currentQuestion, setCurrentQuestion] = useState(0)
  const [attemptStarted, setAttemptStarted] = useState(false)
  const [submitted, setSubmitted] = useState(false)
  const [secondsRemaining, setSecondsRemaining] = useState(
    quiz.durationMinutes * 60,
  )
  const [helpOpen, setHelpOpen] = useState(false)
  const [submitOpen, setSubmitOpen] = useState(false)
  const [showExplanations, setShowExplanations] = useState(false)

  useEffect(() => {
    window.scrollTo(0, 0)
  }, [screen, showExplanations, currentQuestion])

  useEffect(() => {
    if (!attemptStarted || submitted) return undefined

    const timer = window.setInterval(() => {
      setSecondsRemaining((current) => Math.max(0, current - 1))
    }, 1000)

    return () => window.clearInterval(timer)
  }, [attemptStarted, submitted])

  function navigate(nextScreen) {
    setShowExplanations(false)
    setScreen(nextScreen)
  }

  function startAttempt() {
    setAnswers({})
    setCurrentQuestion(0)
    setSecondsRemaining(quiz.durationMinutes * 60)
    setAttemptStarted(true)
    setSubmitted(false)
    setScreen(screens.quiz)
  }

  function recordAnswer(questionId, choiceId) {
    setAnswers((current) => ({ ...current, [questionId]: choiceId }))
  }

  function editQuestion(index) {
    setCurrentQuestion(index)
    setScreen(screens.quiz)
  }

  function confirmSubmit() {
    setSubmitOpen(false)
    setSubmitted(true)
    setScreen(screens.results)
  }

  const unansweredCount = questions.length - Object.keys(answers).length
  const attemptActive = attemptStarted && !submitted
  const focusMode = [screens.quiz, screens.review].includes(screen)

  let content
  if (showExplanations) {
    content = (
      <ExplanationReview
        answers={answers}
        onClose={() => setShowExplanations(false)}
      />
    )
  } else {
    switch (screen) {
      case screens.join:
        content = (
          <JoinQuiz
            onBack={() => navigate(screens.dashboard)}
            onFound={() => navigate(screens.instructions)}
          />
        )
        break
      case screens.instructions:
        content = (
          <Instructions
            onBack={() => navigate(screens.join)}
            onStart={startAttempt}
          />
        )
        break
      case screens.quiz:
        content = (
          <QuizRunner
            answers={answers}
            currentQuestion={currentQuestion}
            onAnswer={recordAnswer}
            onCurrentQuestion={setCurrentQuestion}
            onReview={() => navigate(screens.review)}
            secondsRemaining={secondsRemaining}
          />
        )
        break
      case screens.review:
        content = (
          <ReviewAnswers
            answers={answers}
            onBack={() => navigate(screens.quiz)}
            onEdit={editQuestion}
            onSubmit={() => setSubmitOpen(true)}
            secondsRemaining={secondsRemaining}
          />
        )
        break
      case screens.results:
        content = (
          <ResultsPreview
            answers={answers}
            onDashboard={() => navigate(screens.dashboard)}
            onReview={() => setShowExplanations(true)}
          />
        )
        break
      default:
        content = (
          <Dashboard
            onJoin={() => navigate(screens.join)}
            onOpenInstructions={() => navigate(screens.instructions)}
          />
        )
    }
  }

  return (
    <div className={focusMode ? 'app app--focus' : 'app'}>
      <a className="skip-link" href="#main-content">
        Skip to main content
      </a>
      {!focusMode ? (
        <AppHeader
          currentScreen={screen}
          onHelp={() => setHelpOpen(true)}
          onNavigate={navigate}
          onSwitchAdmin={onSwitchAdmin}
          onSwitchExperience={onSwitchExperience}
          submitted={submitted}
        />
      ) : null}
      {content}
      {!focusMode ? (
        <>
          <footer className="app-footer">
            <span>MARKADO prototype · 2026</span>
            <span>Built for clear, calm assessment experiences.</span>
          </footer>
          <StudentBottomNav
            currentScreen={screen}
            onHelp={() => setHelpOpen(true)}
            onNavigate={navigate}
            submitted={submitted}
          />
        </>
      ) : null}
      {helpOpen ? (
        <HelpPanel attemptActive={attemptActive} onClose={() => setHelpOpen(false)} />
      ) : null}
      {submitOpen ? (
        <SubmitDialog
          unansweredCount={unansweredCount}
          onCancel={() => setSubmitOpen(false)}
          onConfirm={confirmSubmit}
        />
      ) : null}
    </div>
  )
}

export default function App() {
  const [experience, setExperience] = useState(() => {
    if (window.location.hash === '#admin') return 'admin'
    if (window.location.hash === '#professor') return 'professor'
    return 'student'
  })

  if (experience === 'admin') {
    return (
      <AdminApp
        onProfessor={() => setExperience('professor')}
        onStudent={() => setExperience('student')}
      />
    )
  }

  if (experience === 'professor') {
    return (
      <ProfessorApp
        onAdmin={() => setExperience('admin')}
        onStudent={() => setExperience('student')}
      />
    )
  }

  return (
    <StudentPrototype
      onSwitchAdmin={() => setExperience('admin')}
      onSwitchExperience={() => setExperience('professor')}
    />
  )
}
