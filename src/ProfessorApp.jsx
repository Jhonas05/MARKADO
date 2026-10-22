import { useEffect, useMemo, useState } from 'react'
import BrandMark from './components/BrandMark.jsx'
import Icon from './components/Icon.jsx'
import {
  generatedDrafts,
  managedQuizzes,
  professor,
  professorHelpTopics,
  professorQuestions,
  submissionRows,
} from './data/professorPrototype.js'
import './professor.css'

const screens = {
  dashboard: 'dashboard',
  quizzes: 'quizzes',
  create: 'create',
  editor: 'editor',
  aiWorkspace: 'ai-workspace',
  aiReview: 'ai-review',
  schedule: 'schedule',
  randomization: 'randomization',
  preview: 'preview',
  publish: 'publish',
  monitoring: 'monitoring',
  results: 'results',
  exports: 'exports',
  help: 'help',
}

const navigation = [
  {
    label: 'Workspace',
    items: [
      { id: screens.dashboard, label: 'Dashboard', icon: 'dashboard' },
      { id: screens.quizzes, label: 'My quizzes', icon: 'book' },
    ],
  },
  {
    label: 'Build',
    items: [
      { id: screens.create, label: 'Create quiz', icon: 'plus' },
      { id: screens.editor, label: 'Manual editor', icon: 'edit' },
      { id: screens.aiWorkspace, label: 'AI generation', icon: 'spark' },
      { id: screens.aiReview, label: 'Review AI drafts', icon: 'checkCircle' },
      { id: screens.schedule, label: 'Timer & schedule', icon: 'clock' },
      { id: screens.randomization, label: 'Randomization', icon: 'shuffle' },
      { id: screens.preview, label: 'Quiz preview', icon: 'eye' },
      { id: screens.publish, label: 'Publish & share', icon: 'send' },
    ],
  },
  {
    label: 'Responses',
    items: [
      { id: screens.monitoring, label: 'Monitoring', icon: 'users' },
      { id: screens.results, label: 'Results', icon: 'barChart' },
      { id: screens.exports, label: 'PDF & Word exports', icon: 'download' },
    ],
  },
  {
    label: 'Support',
    items: [{ id: screens.help, label: 'Help & guide', icon: 'help' }],
  },
]

const screenLabels = Object.fromEntries(
  navigation.flatMap((group) => group.items.map((item) => [item.id, item.label])),
)

function StatusBadge({ children, tone = 'neutral' }) {
  return <span className={`prof-status prof-status--${tone}`}>{children}</span>
}

function PrototypeBanner() {
  return (
    <div className="prof-prototype-banner" role="note">
      <Icon name="spark" size={17} />
      <p>
        <strong>Professor UI prototype.</strong> All learners, activity, scores,
        AI output, exports, and publishing actions are synthetic simulations.
      </p>
    </div>
  )
}

function ScreenHeader({ eyebrow, title, description, actions }) {
  return (
    <header className="prof-page-heading">
      <div>
        <p className="section-kicker">{eyebrow}</p>
        <h1>{title}</h1>
        {description ? <p>{description}</p> : null}
      </div>
      {actions ? <div className="prof-page-heading__actions">{actions}</div> : null}
    </header>
  )
}

function MetricCard({ icon, label, value, detail, tone = 'green' }) {
  return (
    <article className="prof-metric">
      <span className={`prof-metric__icon prof-metric__icon--${tone}`}>
        <Icon name={icon} />
      </span>
      <div>
        <strong>{value}</strong>
        <span>{label}</span>
      </div>
      <small>{detail}</small>
    </article>
  )
}

function ProfessorHeader({ currentScreen, onAdmin, onMenu, onStudent }) {
  return (
    <header className="prof-header">
      <div className="prof-header__brand">
        <button
          className="prof-mobile-menu"
          type="button"
          onClick={onMenu}
          aria-label="Open professor navigation"
        >
          <Icon name="menu" />
        </button>
        <BrandMark />
        <span>
          <strong>MARKADO</strong>
          <small>Professor workspace</small>
        </span>
      </div>
      <div className="prof-header__context">
        <small>Professor prototype</small>
        <strong>{screenLabels[currentScreen]}</strong>
      </div>
      <div className="prof-header__actions">
        <button className="prof-view-switch" type="button" onClick={onStudent}>
          <Icon name="user" size={17} />
          <span>Student view</span>
        </button>
        <button className="prof-view-switch" type="button" onClick={onAdmin}>
          <Icon name="shield" size={17} />
          <span>Administrator view</span>
        </button>
        <div className="prof-profile" aria-label={`Signed in as ${professor.name}`}>
          <span>{professor.initials}</span>
          <div>
            <strong>{professor.shortName}</strong>
            <small>{professor.department}</small>
          </div>
        </div>
      </div>
    </header>
  )
}

function ProfessorSidebar({ currentScreen, mobileOpen, onClose, onNavigate }) {
  return (
    <>
      {mobileOpen ? (
        <button
          className="prof-nav-backdrop"
          type="button"
          aria-label="Close professor navigation"
          onClick={onClose}
        />
      ) : null}
      <aside
        className={`prof-sidebar${mobileOpen ? ' prof-sidebar--open' : ''}`}
        aria-label="Professor navigation"
      >
        <div className="prof-sidebar__intro">
          <span>Active prototype</span>
          <strong>General Biology</strong>
          <small>BIO 104 · Synthetic workspace</small>
        </div>
        <nav aria-label="Professor navigation">
          {navigation.map((group) => (
            <div className="prof-nav-group" key={group.label}>
              <p>{group.label}</p>
              {group.items.map((item) => (
                <button
                  className={
                    currentScreen === item.id
                      ? 'prof-nav-link prof-nav-link--active'
                      : 'prof-nav-link'
                  }
                  type="button"
                  key={item.id}
                  onClick={() => onNavigate(item.id)}
                  aria-current={currentScreen === item.id ? 'page' : undefined}
                >
                  <Icon name={item.icon} size={17} />
                  <span>{item.label}</span>
                </button>
              ))}
            </div>
          ))}
        </nav>
        <div className="prof-sidebar__policy">
          <Icon name="shield" size={18} />
          <div>
            <strong>Policy-aware prototype</strong>
            <span>Open academic choices remain visibly unapproved.</span>
          </div>
        </div>
      </aside>
    </>
  )
}

function ProfessorDashboard({ onNavigate }) {
  return (
    <>
      <PrototypeBanner />
      <ScreenHeader
        eyebrow="Professor dashboard"
        title="Good afternoon, Prof. Reyes."
        description="Keep today’s quiz moving, review drafts, and check learner progress from one calm workspace."
        actions={
          <button
            className="button button--primary"
            type="button"
            onClick={() => onNavigate(screens.create)}
          >
            <Icon name="plus" size={18} />
            Create quiz
          </button>
        }
      />

      <section className="prof-metric-grid" aria-label="Professor summary">
        <MetricCard icon="book" value="4" label="Quizzes" detail="1 active now" />
        <MetricCard
          icon="users"
          value="27 / 32"
          label="Submitted"
          detail="BIO 104"
          tone="blue"
        />
        <MetricCard
          icon="spark"
          value="2"
          label="Drafts to review"
          detail="AI-assisted"
          tone="amber"
        />
        <MetricCard
          icon="barChart"
          value="84%"
          label="Synthetic average"
          detail="Submitted attempts"
          tone="green"
        />
      </section>

      <div className="prof-dashboard-grid">
        <section className="prof-panel prof-live-card" aria-labelledby="live-quiz-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Live practice</p>
              <h2 id="live-quiz-title">Ecosystems checkpoint</h2>
            </div>
            <StatusBadge tone="success">Published</StatusBadge>
          </div>
          <div className="prof-live-progress">
            <div>
              <span>Submissions</span>
              <strong>27 of 32</strong>
            </div>
            <span>84%</span>
          </div>
          <div className="prof-progress" aria-hidden="true">
            <span style={{ width: '84%' }} />
          </div>
          <dl className="prof-inline-details">
            <div>
              <dt>Quiz code</dt>
              <dd>BIO-104</dd>
            </div>
            <div>
              <dt>Section</dt>
              <dd>BSBIO 1-A</dd>
            </div>
            <div>
              <dt>Window</dt>
              <dd>4:00–5:00 PM</dd>
            </div>
          </dl>
          <div className="prof-card-actions">
            <button
              className="button button--primary"
              type="button"
              onClick={() => onNavigate(screens.monitoring)}
            >
              Monitor submissions
              <Icon name="arrowRight" size={17} />
            </button>
            <button
              className="button button--secondary"
              type="button"
              onClick={() => onNavigate(screens.results)}
            >
              View results
            </button>
          </div>
        </section>

        <section className="prof-panel" aria-labelledby="attention-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Needs attention</p>
              <h2 id="attention-title">Review queue</h2>
            </div>
            <span className="prof-count">2</span>
          </div>
          <div className="prof-task-list">
            <button type="button" onClick={() => onNavigate(screens.aiReview)}>
              <span className="prof-task-list__icon prof-task-list__icon--amber">
                <Icon name="spark" size={18} />
              </span>
              <span>
                <strong>Two AI-assisted drafts are pending</strong>
                <small>Check source support, answer, and wording.</small>
              </span>
              <Icon name="chevronRight" size={18} />
            </button>
            <button type="button" onClick={() => onNavigate(screens.schedule)}>
              <span className="prof-task-list__icon">
                <Icon name="clock" size={18} />
              </span>
              <span>
                <strong>Schedule needs a policy note</strong>
                <small>Expiry behavior is intentionally unapproved.</small>
              </span>
              <Icon name="chevronRight" size={18} />
            </button>
          </div>
        </section>

        <section className="prof-panel prof-recent" aria-labelledby="recent-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Recent quizzes</p>
              <h2 id="recent-title">Your assessments</h2>
            </div>
            <button
              className="prof-text-button"
              type="button"
              onClick={() => onNavigate(screens.quizzes)}
            >
              View all <Icon name="arrowRight" size={16} />
            </button>
          </div>
          <div className="prof-quiz-rows">
            {managedQuizzes.slice(0, 3).map((quiz) => (
              <article key={quiz.id}>
                <span className="prof-course-tile">{quiz.code.split('-')[0]}</span>
                <div>
                  <strong>{quiz.title}</strong>
                  <small>
                    {quiz.course} · {quiz.section}
                  </small>
                </div>
                <StatusBadge
                  tone={
                    quiz.status === 'Published'
                      ? 'success'
                      : quiz.status === 'Draft'
                        ? 'warning'
                        : 'info'
                  }
                >
                  {quiz.status}
                </StatusBadge>
                <small>{quiz.updated}</small>
              </article>
            ))}
          </div>
        </section>
      </div>
    </>
  )
}

function QuizManagement({ onNavigate, onToast }) {
  const [query, setQuery] = useState('')
  const [filter, setFilter] = useState('All')
  const filteredQuizzes = managedQuizzes.filter((quiz) => {
    const matchesFilter = filter === 'All' || quiz.status === filter
    const normalizedQuery = query.trim().toLowerCase()
    const matchesQuery =
      !normalizedQuery ||
      `${quiz.title} ${quiz.course} ${quiz.code}`.toLowerCase().includes(normalizedQuery)
    return matchesFilter && matchesQuery
  })

  return (
    <>
      <ScreenHeader
        eyebrow="Quiz management"
        title="My quizzes"
        description="Manage synthetic drafts and published assessments without changing the Student prototype."
        actions={
          <button
            className="button button--primary"
            type="button"
            onClick={() => onNavigate(screens.create)}
          >
            <Icon name="plus" size={18} />
            New quiz
          </button>
        }
      />
      <section className="prof-panel prof-toolbar" aria-label="Quiz filters">
        <label className="prof-search">
          <span className="sr-only">Search quizzes</span>
          <Icon name="search" size={18} />
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search title, course, or code"
          />
        </label>
        <div className="prof-filter-tabs" aria-label="Filter quizzes by status">
          {['All', 'Draft', 'Scheduled', 'Published', 'Closed'].map((status) => (
            <button
              className={filter === status ? 'prof-filter-tab prof-filter-tab--active' : 'prof-filter-tab'}
              type="button"
              key={status}
              onClick={() => setFilter(status)}
            >
              {status}
            </button>
          ))}
        </div>
      </section>

      <section className="prof-quiz-grid" aria-label="Quiz list">
        {filteredQuizzes.map((quiz) => (
          <article className="prof-quiz-card" key={quiz.id}>
            <div className="prof-quiz-card__top">
              <span className="prof-course-tile">{quiz.code.split('-')[0]}</span>
              <StatusBadge
                tone={
                  quiz.status === 'Published'
                    ? 'success'
                    : quiz.status === 'Draft'
                      ? 'warning'
                      : quiz.status === 'Closed'
                        ? 'neutral'
                        : 'info'
                }
              >
                {quiz.status}
              </StatusBadge>
            </div>
            <p>{quiz.course} · {quiz.section}</p>
            <h2>{quiz.title}</h2>
            <dl>
              <div>
                <dt>Questions</dt>
                <dd>{quiz.questions}</dd>
              </div>
              <div>
                <dt>Submissions</dt>
                <dd>{quiz.submissions} / {quiz.learners}</dd>
              </div>
              <div>
                <dt>Schedule</dt>
                <dd>{quiz.schedule}</dd>
              </div>
            </dl>
            <div className="prof-quiz-card__footer">
              <small>Updated {quiz.updated}</small>
              <div>
                <button
                  className="icon-button"
                  type="button"
                  aria-label={`Duplicate ${quiz.title} (mock)`}
                  onClick={() => onToast(`Mock duplicate created for “${quiz.title}”.`)}
                >
                  <Icon name="copy" size={17} />
                </button>
                <button
                  className="button button--secondary button--compact"
                  type="button"
                  onClick={() =>
                    onNavigate(
                      quiz.status === 'Published' ? screens.monitoring : screens.editor,
                    )
                  }
                >
                  {quiz.status === 'Published' ? 'Monitor' : 'Manage'}
                </button>
              </div>
            </div>
          </article>
        ))}
      </section>
      {!filteredQuizzes.length ? (
        <div className="prof-empty" role="status">
          <Icon name="search" size={28} />
          <h2>No quizzes match this view.</h2>
          <p>Clear the search or choose a different status.</p>
        </div>
      ) : null}
    </>
  )
}

function BuilderSteps({ current, onNavigate }) {
  const steps = [
    { id: screens.create, number: 1, label: 'Identity' },
    { id: screens.editor, number: 2, label: 'Questions' },
    { id: screens.schedule, number: 3, label: 'Rules' },
    { id: screens.preview, number: 4, label: 'Preview' },
    { id: screens.publish, number: 5, label: 'Publish' },
  ]
  return (
    <nav className="prof-builder-steps" aria-label="Quiz builder steps">
      {steps.map((step) => (
        <button
          className={current === step.id ? 'prof-builder-step prof-builder-step--active' : 'prof-builder-step'}
          type="button"
          key={step.id}
          onClick={() => onNavigate(step.id)}
          aria-current={current === step.id ? 'step' : undefined}
        >
          <span>{step.number}</span>
          <strong>{step.label}</strong>
        </button>
      ))}
    </nav>
  )
}

function CreateQuiz({ draft, onDraftChange, onNavigate, onToast }) {
  return (
    <>
      <ScreenHeader
        eyebrow="Create quiz wizard"
        title="Create a new quiz"
        description="Build a policy-aware assessment in five reviewable steps. Nothing is saved to a server in this prototype."
      />
      <BuilderSteps current={screens.create} onNavigate={onNavigate} />
      <div className="prof-form-layout">
        <section className="prof-panel prof-form-card" aria-labelledby="quiz-identity-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Step 1 of 5</p>
              <h2 id="quiz-identity-title">Quiz identity</h2>
            </div>
            <StatusBadge tone="warning">Draft</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field prof-field--wide">
              <span>Quiz title</span>
              <input
                value={draft.title}
                onChange={(event) => onDraftChange('title', event.target.value)}
              />
              <small>Shown to eligible learners before they begin.</small>
            </label>
            <label className="prof-field">
              <span>Course</span>
              <select
                value={draft.course}
                onChange={(event) => onDraftChange('course', event.target.value)}
              >
                <option>General Biology</option>
                <option>Environmental Science</option>
                <option>Research Methods</option>
              </select>
            </label>
            <label className="prof-field">
              <span>Intended learners</span>
              <select
                value={draft.section}
                onChange={(event) => onDraftChange('section', event.target.value)}
              >
                <option>BSBIO 1-A (synthetic)</option>
                <option>BSBIO 1-B (synthetic)</option>
                <option>BSES 2-A (synthetic)</option>
              </select>
            </label>
            <label className="prof-field prof-field--wide">
              <span>Student-facing instructions</span>
              <textarea
                rows="5"
                value={draft.instructions}
                onChange={(event) => onDraftChange('instructions', event.target.value)}
              />
              <small>Timer, navigation, and release details must match approved rules.</small>
            </label>
          </div>
          <div className="prof-form-actions">
            <button
              className="button button--secondary"
              type="button"
              onClick={() => onToast('Draft changes are held locally in this prototype.')}
            >
              Save mock draft
            </button>
            <button
              className="button button--primary"
              type="button"
              disabled={!draft.title.trim()}
              onClick={() => onNavigate(screens.editor)}
            >
              Continue to questions
              <Icon name="arrowRight" size={18} />
            </button>
          </div>
        </section>
        <aside className="prof-side-card">
          <Icon name="shield" size={22} />
          <h2>Before you continue</h2>
          <p>
            Intended learner and class structures remain prototype assumptions.
            Production eligibility must be authorized by Laravel on every request.
          </p>
          <ul>
            <li>Use synthetic learner data only.</li>
            <li>Keep instructions accurate and plain.</li>
            <li>Review every publish-time warning.</li>
          </ul>
        </aside>
      </div>
    </>
  )
}

function QuestionEditor({ questions, setQuestions, onNavigate, onToast }) {
  const [selectedId, setSelectedId] = useState(questions[0].id)
  const selectedIndex = Math.max(
    0,
    questions.findIndex((question) => question.id === selectedId),
  )
  const selected = questions[selectedIndex]

  function updateQuestion(field, value) {
    setQuestions((current) =>
      current.map((question) =>
        question.id === selected.id ? { ...question, [field]: value } : question,
      ),
    )
  }

  function updateChoice(index, value) {
    setQuestions((current) =>
      current.map((question) =>
        question.id === selected.id
          ? {
              ...question,
              choices: question.choices.map((choice, choiceIndex) =>
                choiceIndex === index ? value : choice,
              ),
            }
          : question,
      ),
    )
  }

  function addQuestion() {
    const id = `pq-${questions.length + 1}`
    setQuestions((current) => [
      ...current,
      {
        id,
        type: 'Multiple choice',
        topic: 'New topic',
        prompt: 'Write the question prompt here.',
        choices: ['Option A', 'Option B', 'Option C', 'Option D'],
        correctIndex: 0,
        points: 1,
      },
    ])
    setSelectedId(id)
    onToast('A blank mock question was added.')
  }

  return (
    <>
      <ScreenHeader
        eyebrow="Manual question editor"
        title="Manual question editor"
        description="Edit stable question records and identify the proposed correct option for this local prototype."
        actions={
          <button className="button button--primary" type="button" onClick={addQuestion}>
            <Icon name="plus" size={18} />
            Add question
          </button>
        }
      />
      <BuilderSteps current={screens.editor} onNavigate={onNavigate} />
      <div className="prof-editor-layout">
        <aside className="prof-question-list" aria-label="Quiz questions">
          <div>
            <span>{questions.length} questions</span>
            <strong>{questions.reduce((sum, item) => sum + Number(item.points), 0)} points</strong>
          </div>
          {questions.map((question, index) => (
            <button
              className={question.id === selected.id ? 'prof-question-item prof-question-item--active' : 'prof-question-item'}
              type="button"
              key={question.id}
              onClick={() => setSelectedId(question.id)}
              aria-current={question.id === selected.id ? 'true' : undefined}
            >
              <span>{index + 1}</span>
              <span>
                <strong>{question.prompt}</strong>
                <small>{question.type} · {question.points} point</small>
              </span>
              <Icon name="chevronRight" size={16} />
            </button>
          ))}
          <button
            className="prof-ai-entry"
            type="button"
            onClick={() => onNavigate(screens.aiWorkspace)}
          >
            <Icon name="spark" size={18} />
            <span>
              <strong>Draft with AI assistance</strong>
              <small>Human approval is always required.</small>
            </span>
          </button>
        </aside>

        <section className="prof-panel prof-question-editor" aria-labelledby="question-editor-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Question {selectedIndex + 1}</p>
              <h2 id="question-editor-title">Question details</h2>
            </div>
            <StatusBadge tone="success">Manually authored</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field">
              <span>Question type</span>
              <select value={selected.type} onChange={(event) => updateQuestion('type', event.target.value)}>
                <option>Multiple choice</option>
                <option disabled>True / False — policy open</option>
                <option disabled>Essay — grading policy open</option>
              </select>
            </label>
            <label className="prof-field">
              <span>Topic label</span>
              <input value={selected.topic} onChange={(event) => updateQuestion('topic', event.target.value)} />
            </label>
            <label className="prof-field prof-field--wide">
              <span>Prompt</span>
              <textarea rows="3" value={selected.prompt} onChange={(event) => updateQuestion('prompt', event.target.value)} />
            </label>
          </div>
          <fieldset className="prof-choice-editor">
            <legend>Answer choices and proposed correct answer</legend>
            {selected.choices.map((choice, index) => (
              <label key={`${selected.id}-${index}`}>
                <input
                  type="radio"
                  name={`correct-${selected.id}`}
                  checked={selected.correctIndex === index}
                  onChange={() => updateQuestion('correctIndex', index)}
                />
                <span>{String.fromCharCode(65 + index)}</span>
                <input
                  aria-label={`Choice ${String.fromCharCode(65 + index)}`}
                  value={choice}
                  onChange={(event) => updateChoice(index, event.target.value)}
                />
              </label>
            ))}
          </fieldset>
          <div className="prof-editor-footer">
            <label className="prof-points-field">
              <span>Points</span>
              <input
                type="number"
                min="1"
                max="10"
                value={selected.points}
                onChange={(event) => updateQuestion('points', event.target.value)}
              />
            </label>
            <div>
              <button
                className="button button--secondary"
                type="button"
                onClick={() => onToast('Question changes are held locally in this prototype.')}
              >
                Save mock question
              </button>
              <button
                className="button button--primary"
                type="button"
                onClick={() => onNavigate(screens.schedule)}
              >
                Continue to rules
                <Icon name="arrowRight" size={17} />
              </button>
            </div>
          </div>
        </section>
      </div>
    </>
  )
}

function AiWorkspace({ onNavigate, onToast }) {
  const [sourceReady, setSourceReady] = useState(false)
  const [count, setCount] = useState('3')
  const [difficulty, setDifficulty] = useState('Mixed')

  function simulateGeneration() {
    if (!sourceReady) {
      onToast('Choose the synthetic sample material before generating drafts.')
      return
    }
    onToast('Three synthetic drafts were generated locally for human review.')
    onNavigate(screens.aiReview)
  }

  return (
    <>
      <ScreenHeader
        eyebrow="AI question generation workspace"
        title="AI question generation"
        description="Configure a mock generation job. No file is uploaded and no external AI service is contacted."
      />
      <div className="prof-ai-layout">
        <section className="prof-panel prof-ai-source" aria-labelledby="source-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">1 · Source</p>
              <h2 id="source-title">Learning material</h2>
            </div>
            {sourceReady ? <StatusBadge tone="success">Sample ready</StatusBadge> : null}
          </div>
          <button
            className={sourceReady ? 'prof-upload-zone prof-upload-zone--ready' : 'prof-upload-zone'}
            type="button"
            onClick={() => setSourceReady(true)}
          >
            <span><Icon name={sourceReady ? 'checkCircle' : 'upload'} size={26} /></span>
            <strong>{sourceReady ? 'Biology_Module_3_SAMPLE.pdf' : 'Use synthetic sample material'}</strong>
            <small>
              {sourceReady
                ? '12 mock pages · no real file left this browser'
                : 'Prototype substitute for a future secure upload flow'}
            </small>
          </button>
          <div className="prof-warning-note">
            <Icon name="alertCircle" size={18} />
            <p>
              Accepted file types, OCR, provider, storage, retention, ownership,
              and cost are <strong>not approved yet</strong>.
            </p>
          </div>
        </section>

        <section className="prof-panel prof-ai-config" aria-labelledby="generation-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">2 · Configure</p>
              <h2 id="generation-title">Draft request</h2>
            </div>
            <StatusBadge tone="info">Mock action</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field prof-field--wide">
              <span>Topic or learning outcome</span>
              <input defaultValue="Energy flow and nutrient cycling" />
            </label>
            <label className="prof-field">
              <span>Number of drafts</span>
              <select value={count} onChange={(event) => setCount(event.target.value)}>
                <option>3</option>
                <option>5</option>
                <option>10</option>
              </select>
            </label>
            <label className="prof-field">
              <span>Difficulty descriptor</span>
              <select value={difficulty} onChange={(event) => setDifficulty(event.target.value)}>
                <option>Mixed</option>
                <option>Foundational</option>
                <option>Intermediate</option>
              </select>
            </label>
            <label className="prof-field prof-field--wide">
              <span>Question format</span>
              <select defaultValue="Multiple choice only">
                <option>Multiple choice only</option>
                <option disabled>Other formats — V1 policy open</option>
              </select>
            </label>
          </div>
          <div className="prof-ai-promise">
            <Icon name="shield" size={20} />
            <p>
              Drafts remain excluded from the quiz until a professor verifies the
              source, wording, answer, and level.
            </p>
          </div>
          <button className="button button--primary button--full" type="button" onClick={simulateGeneration}>
            <Icon name="spark" size={18} />
            Simulate {count}-question generation
          </button>
        </section>
      </div>
    </>
  )
}

function AiReview({ drafts, setDrafts, onNavigate, onToast }) {
  const [selectedId, setSelectedId] = useState(drafts[0].id)
  const selected = drafts.find((draft) => draft.id === selectedId) ?? drafts[0]
  const approvedCount = drafts.filter((draft) => draft.status === 'approved').length
  const pendingCount = drafts.filter((draft) => draft.status === 'pending').length

  function decide(status) {
    setDrafts((current) =>
      current.map((draft) => (draft.id === selected.id ? { ...draft, status } : draft)),
    )
    onToast(
      status === 'approved'
        ? 'Draft approved for inclusion in this mock revision.'
        : 'Draft rejected and excluded from this mock revision.',
    )
  }

  return (
    <>
      <ScreenHeader
        eyebrow="AI draft review and approval"
        title="Review AI drafts"
        description="Human approval is required. Compare each draft with its synthetic source excerpt before making a decision."
        actions={
          <div className="prof-review-summary">
            <span><strong>{approvedCount}</strong> approved</span>
            <span><strong>{pendingCount}</strong> pending</span>
          </div>
        }
      />
      <div className="prof-review-layout">
        <aside className="prof-draft-list" aria-label="AI-assisted draft questions">
          {drafts.map((draft, index) => (
            <button
              className={draft.id === selected.id ? 'prof-draft-item prof-draft-item--active' : 'prof-draft-item'}
              type="button"
              key={draft.id}
              onClick={() => setSelectedId(draft.id)}
            >
              <span>{index + 1}</span>
              <span>
                <strong>{draft.prompt}</strong>
                <small>{draft.source}</small>
              </span>
              <StatusBadge
                tone={
                  draft.status === 'approved'
                    ? 'success'
                    : draft.status === 'rejected'
                      ? 'danger'
                      : 'warning'
                }
              >
                {draft.status}
              </StatusBadge>
            </button>
          ))}
        </aside>

        <section className="prof-panel prof-draft-review" aria-labelledby="draft-title">
          <div className="prof-ai-label">
            <Icon name="spark" size={17} />
            AI-assisted draft · check before approving
          </div>
          <p className="prof-source-label">{selected.source}</p>
          <h2 id="draft-title">{selected.prompt}</h2>
          <div className="prof-draft-choices">
            {selected.choices.map((choice, index) => (
              <div
                className={index === selected.proposedAnswer ? 'prof-draft-choice prof-draft-choice--proposed' : 'prof-draft-choice'}
                key={choice}
              >
                <span>{String.fromCharCode(65 + index)}</span>
                <p>{choice}</p>
                {index === selected.proposedAnswer ? <strong>Proposed answer</strong> : null}
              </div>
            ))}
          </div>
          <div className="prof-source-excerpt">
            <span>Proposed explanation</span>
            <p>{selected.explanation}</p>
          </div>
          <div className="prof-review-checks" aria-label="Review checklist">
            <span><Icon name="check" size={15} /> Source is visible</span>
            <span><Icon name="check" size={15} /> Answer can be edited</span>
            <span><Icon name="check" size={15} /> Approval is recorded locally</span>
          </div>
          <div className="prof-review-actions">
            <button className="button button--danger-soft" type="button" onClick={() => decide('rejected')}>
              Reject draft
            </button>
            <button
              className="button button--secondary"
              type="button"
              onClick={() => onToast('Mock edit mode opened; no source content was changed.')}
            >
              Edit wording
            </button>
            <button className="button button--primary" type="button" onClick={() => decide('approved')}>
              <Icon name="check" size={17} />
              Approve draft
            </button>
          </div>
        </section>
      </div>
      <div className="prof-sticky-action">
        <span>
          <strong>{pendingCount ? `${pendingCount} draft${pendingCount === 1 ? '' : 's'} still need review.` : 'Every draft has a decision.'}</strong>
          <small>Pending and rejected items remain excluded from publishing.</small>
        </span>
        <button className="button button--primary" type="button" onClick={() => onNavigate(screens.schedule)}>
          Continue to quiz rules
          <Icon name="arrowRight" size={17} />
        </button>
      </div>
    </>
  )
}

function ScheduleSettings({ settings, setSettings, onNavigate, onToast }) {
  function update(field, value) {
    setSettings((current) => ({ ...current, [field]: value }))
  }

  return (
    <>
      <ScreenHeader
        eyebrow="Timer and scheduling configuration"
        title="Timer and schedule"
        description="Keep availability, whole-quiz duration, and per-question timing visibly separate."
      />
      <BuilderSteps current={screens.schedule} onNavigate={onNavigate} />
      <div className="prof-settings-grid">
        <section className="prof-panel prof-settings-card" aria-labelledby="availability-title">
          <div className="prof-setting-icon"><Icon name="calendar" /></div>
          <div>
            <p className="section-kicker">Availability window</p>
            <h2 id="availability-title">When learners may access the quiz</h2>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field">
              <span>Opens</span>
              <input type="datetime-local" value={settings.opens} onChange={(event) => update('opens', event.target.value)} />
            </label>
            <label className="prof-field">
              <span>Closes</span>
              <input type="datetime-local" value={settings.closes} onChange={(event) => update('closes', event.target.value)} />
            </label>
            <label className="prof-field prof-field--wide">
              <span>Displayed timezone</span>
              <select value={settings.timezone} onChange={(event) => update('timezone', event.target.value)}>
                <option>Asia/Manila (UTC+08:00)</option>
              </select>
            </label>
          </div>
        </section>

        <section className="prof-panel prof-settings-card" aria-labelledby="duration-title">
          <div className="prof-setting-icon"><Icon name="clock" /></div>
          <div>
            <p className="section-kicker">Quiz session timer</p>
            <h2 id="duration-title">Overall attempt duration</h2>
          </div>
          <label className="prof-duration-input">
            <input type="number" min="1" value={settings.duration} onChange={(event) => update('duration', event.target.value)} />
            <span>minutes</span>
          </label>
          <p>The future Laravel server—not the browser—will determine authoritative deadlines.</p>
        </section>

        <section className="prof-panel prof-settings-card" aria-labelledby="per-question-title">
          <div className="prof-setting-icon"><Icon name="flag" /></div>
          <div>
            <p className="section-kicker">Per-question timer</p>
            <h2 id="per-question-title">Optional question timing</h2>
          </div>
          <label className="prof-switch-row">
            <span>
              <strong>Enable per-question timing</strong>
              <small>Prototype configuration only</small>
            </span>
            <input
              type="checkbox"
              checked={settings.perQuestion}
              onChange={(event) => update('perQuestion', event.target.checked)}
            />
          </label>
          {settings.perQuestion ? (
            <label className="prof-duration-input">
              <input type="number" min="10" defaultValue="60" />
              <span>seconds each</span>
            </label>
          ) : null}
        </section>

        <aside className="prof-policy-card">
          <span><Icon name="alertCircle" size={20} /></span>
          <div>
            <p className="section-kicker">Academic policy open</p>
            <h2>Expiry behavior is not approved.</h2>
            <p>
              Auto-submit, auto-advance, close-window truncation, outage grace,
              accommodations, and concurrent timer rules remain undecided.
            </p>
          </div>
        </aside>
      </div>
      <div className="prof-form-actions prof-form-actions--standalone">
        <button className="button button--secondary" type="button" onClick={() => onToast('Timer settings are held locally in this prototype.')}>
          Save mock settings
        </button>
        <button className="button button--primary" type="button" onClick={() => onNavigate(screens.randomization)}>
          Configure randomization
          <Icon name="arrowRight" size={17} />
        </button>
      </div>
    </>
  )
}

function RandomizationSettings({ randomization, setRandomization, onNavigate }) {
  function toggle(field) {
    setRandomization((current) => ({ ...current, [field]: !current[field] }))
  }
  return (
    <>
      <ScreenHeader
        eyebrow="Question randomization settings"
        title="Question randomization"
        description="Preview how stable question and option IDs can support a different display order for each attempt."
      />
      <div className="prof-random-layout">
        <section className="prof-panel prof-random-card" aria-labelledby="random-controls-title">
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Prototype rules</p>
              <h2 id="random-controls-title">Randomization controls</h2>
            </div>
            <Icon name="shuffle" size={24} />
          </div>
          <label className="prof-setting-row">
            <span>
              <strong>Shuffle question order</strong>
              <small>Create a stored question permutation per attempt.</small>
            </span>
            <input type="checkbox" checked={randomization.questions} onChange={() => toggle('questions')} />
          </label>
          <label className="prof-setting-row">
            <span>
              <strong>Shuffle answer choices</strong>
              <small>Grade by stable option ID, never the displayed letter.</small>
            </span>
            <input type="checkbox" checked={randomization.choices} onChange={() => toggle('choices')} />
          </label>
          <label className="prof-setting-row">
            <span>
              <strong>Keep excluded questions fixed</strong>
              <small>Sequence-dependent and “all of the above” items need review.</small>
            </span>
            <input type="checkbox" checked={randomization.protected} onChange={() => toggle('protected')} />
          </label>
        </section>

        <aside className="prof-panel prof-random-preview" aria-labelledby="order-preview-title">
          <p className="section-kicker">Attempt preview</p>
          <h2 id="order-preview-title">Synthetic learner order</h2>
          <p>Stored IDs remain stable while display positions change.</p>
          <div className="prof-order-list">
            {[
              ['Q3', 'Population change', 'position 1'],
              ['Q1', 'Ecology fundamentals', 'position 2'],
              ['Q2', 'Energy flow', 'position 3'],
            ].map(([id, label, position]) => (
              <div key={id}>
                <span><Icon name="shuffle" size={15} /></span>
                <p><strong>{label}</strong><small>{id} · {position}</small></p>
              </div>
            ))}
          </div>
          <div className="prof-integrity-note">
            <Icon name="shield" size={18} />
            <p>Randomization can vary presentation; it does not guarantee prevention of collusion.</p>
          </div>
        </aside>
      </div>
      <div className="prof-form-actions prof-form-actions--standalone">
        <button className="button button--secondary" type="button" onClick={() => onNavigate(screens.schedule)}>
          Back to timer
        </button>
        <button className="button button--primary" type="button" onClick={() => onNavigate(screens.preview)}>
          Preview quiz
          <Icon name="arrowRight" size={17} />
        </button>
      </div>
    </>
  )
}

function QuizPreview({ draft, questions, settings, onNavigate }) {
  const [device, setDevice] = useState('desktop')
  const question = questions[0]
  return (
    <>
      <ScreenHeader
        eyebrow="Quiz preview"
        title="Quiz preview"
        description="This approximation contains no answer submission, authoritative timer, or official grading behavior."
        actions={
          <div className="prof-device-toggle" aria-label="Preview size">
            <button className={device === 'desktop' ? 'active' : ''} type="button" onClick={() => setDevice('desktop')}>
              Desktop
            </button>
            <button className={device === 'mobile' ? 'active' : ''} type="button" onClick={() => setDevice('mobile')}>
              Mobile
            </button>
          </div>
        }
      />
      <BuilderSteps current={screens.preview} onNavigate={onNavigate} />
      <section className="prof-preview-stage" aria-label={`${device} quiz preview`}>
        <div className={`prof-preview-frame prof-preview-frame--${device}`}>
          <div className="prof-preview-browser">
            <span /><span /><span />
            <small>Student preview · prototype only</small>
          </div>
          <div className="prof-preview-content">
            <div className="prof-preview-toolbar">
              <span><small>GENERAL BIOLOGY</small><strong>{draft.title}</strong></span>
              <span><small>Display-only timer</small><strong>{settings.duration}:00</strong></span>
            </div>
            <div className="prof-preview-question">
              <p>Question 1 of {questions.length}</p>
              <h2>{question.prompt}</h2>
              <fieldset>
                <legend className="sr-only">Preview answer choices</legend>
                {question.choices.map((choice, index) => (
                  <label key={choice}>
                    <input type="radio" name="preview-answer" />
                    <span>{String.fromCharCode(65 + index)}</span>
                    {choice}
                  </label>
                ))}
              </fieldset>
              <div className="prof-preview-actions">
                <button className="button button--secondary" type="button" disabled>Previous</button>
                <button className="button button--primary" type="button">Next question</button>
              </div>
            </div>
          </div>
        </div>
      </section>
      <div className="prof-sticky-action">
        <span><strong>Preview checked?</strong><small>Verify wording, timer labels, navigation, and mobile layout.</small></span>
        <button className="button button--primary" type="button" onClick={() => onNavigate(screens.publish)}>
          Continue to publishing
          <Icon name="arrowRight" size={17} />
        </button>
      </div>
    </>
  )
}

function Publishing({ drafts, published, setPublished, onNavigate, onToast }) {
  const [confirmed, setConfirmed] = useState(false)
  const [copied, setCopied] = useState('')
  const approvedCount = drafts.filter((draft) => draft.status === 'approved').length
  const pendingCount = drafts.filter((draft) => draft.status === 'pending').length

  async function copyValue(label, value) {
    try {
      await window.navigator.clipboard?.writeText(value)
    } catch {
      // Clipboard access is optional in this prototype; visual feedback still works.
    }
    setCopied(label)
    onToast(`${label} copied for this prototype demonstration.`)
  }

  if (published) {
    return (
      <>
        <ScreenHeader
          eyebrow="Quiz publishing and sharing"
          title="Your mock quiz is ready to share."
          description="This receipt simulates a published revision; no student can access it and no server record was created."
        />
        <section className="prof-publish-success" aria-labelledby="published-title">
          <span className="prof-publish-success__icon"><Icon name="check" size={28} /></span>
          <p className="section-kicker">Mock revision published</p>
          <h2 id="published-title">Ecosystems checkpoint</h2>
          <p>Share the demonstration code or link during prototype review.</p>
          <div className="prof-share-grid">
            <div>
              <span>Quiz code</span>
              <strong>BIO-104</strong>
              <button type="button" onClick={() => copyValue('Quiz code', 'BIO-104')}>
                <Icon name="copy" size={16} /> {copied === 'Quiz code' ? 'Copied' : 'Copy'}
              </button>
            </div>
            <div>
              <span>Prototype link</span>
              <strong>markado.test/join/BIO-104</strong>
              <button type="button" onClick={() => copyValue('Prototype link', 'https://markado.test/join/BIO-104')}>
                <Icon name="copy" size={16} /> {copied === 'Prototype link' ? 'Copied' : 'Copy'}
              </button>
            </div>
          </div>
          <div className="prof-card-actions prof-card-actions--center">
            <button className="button button--secondary" type="button" onClick={() => setPublished(false)}>Return to checklist</button>
            <button className="button button--primary" type="button" onClick={() => onNavigate(screens.monitoring)}>
              Open monitoring
              <Icon name="arrowRight" size={17} />
            </button>
          </div>
        </section>
      </>
    )
  }

  return (
    <>
      <ScreenHeader
        eyebrow="Quiz publishing and sharing"
        title="Publish and share"
        description="Complete the review receipt before simulating a new quiz revision."
      />
      <BuilderSteps current={screens.publish} onNavigate={onNavigate} />
      <div className="prof-publish-layout">
        <section className="prof-panel prof-publish-checklist" aria-labelledby="publish-checklist-title">
          <div className="prof-panel-heading">
            <div><p className="section-kicker">Publish checklist</p><h2 id="publish-checklist-title">Revision readiness</h2></div>
            <StatusBadge tone="success">Ready for mock publish</StatusBadge>
          </div>
          {[
            ['Quiz identity', 'Title, course, and synthetic section are present.', true],
            ['Question validation', 'Three manual questions have prompts, choices, and answers.', true],
            ['AI review', `${approvedCount} approved; ${pendingCount} pending draft${pendingCount === 1 ? '' : 's'} excluded.`, true],
            ['Timer and schedule', 'Timezone is visible; expiry policy remains clearly unapproved.', true],
            ['Student preview', 'Desktop and mobile preview are available for review.', true],
          ].map(([label, detail, ready]) => (
            <div className="prof-publish-check" key={label}>
              <span className={ready ? 'ready' : ''}><Icon name={ready ? 'check' : 'alertCircle'} size={16} /></span>
              <div><strong>{label}</strong><small>{detail}</small></div>
              <strong>{ready ? 'Checked' : 'Review'}</strong>
            </div>
          ))}
        </section>
        <aside className="prof-publish-summary">
          <p className="section-kicker">Mock revision</p>
          <h2>Ecosystems checkpoint</h2>
          <dl>
            <div><dt>Course</dt><dd>General Biology</dd></div>
            <div><dt>Learners</dt><dd>BSBIO 1-A · synthetic</dd></div>
            <div><dt>Questions</dt><dd>3 manual + {approvedCount} approved AI</dd></div>
            <div><dt>Duration</dt><dd>25 minutes</dd></div>
            <div><dt>Timezone</dt><dd>Asia/Manila</dd></div>
          </dl>
          <label className="prof-confirm-publish">
            <input type="checkbox" checked={confirmed} onChange={(event) => setConfirmed(event.target.checked)} />
            <span>I reviewed this prototype revision and understand that publishing is simulated.</span>
          </label>
          <button className="button button--primary button--full" type="button" disabled={!confirmed} onClick={() => setPublished(true)}>
            <Icon name="send" size={17} />
            Simulate publish
          </button>
          <p className="prof-small-print">No live join code, link, attempt, or academic record will be created.</p>
        </aside>
      </div>
    </>
  )
}

function SubmissionMonitoring({ onNavigate, onToast }) {
  const [filter, setFilter] = useState('All')
  const [selected, setSelected] = useState(null)
  const rows = submissionRows.filter((row) => filter === 'All' || row.status === filter)
  return (
    <>
      <ScreenHeader
        eyebrow="Student submission monitoring"
        title="Monitor submissions"
        description="Synthetic status snapshots demonstrate honest monitoring without claiming real-time surveillance."
        actions={
          <button className="button button--secondary" type="button" onClick={() => onToast('Synthetic monitoring snapshot refreshed just now.')}>
            <Icon name="refresh" size={17} />
            Refresh mock data
          </button>
        }
      />
      <section className="prof-monitor-banner">
        <div><span className="prof-live-dot" /><strong>Ecosystems checkpoint</strong><small>BIO-104 · closes 5:00 PM Asia/Manila</small></div>
        <div><strong>27</strong><span>submitted</span></div>
        <div><strong>3</strong><span>in progress</span></div>
        <div><strong>2</strong><span>not started</span></div>
      </section>
      <section className="prof-panel prof-table-panel">
        <div className="prof-table-toolbar">
          <div className="prof-filter-tabs" aria-label="Filter submissions">
            {['All', 'Submitted', 'In progress', 'Not started'].map((status) => (
              <button className={filter === status ? 'prof-filter-tab prof-filter-tab--active' : 'prof-filter-tab'} type="button" key={status} onClick={() => setFilter(status)}>{status}</button>
            ))}
          </div>
          <small>Updated locally · not connected to a server</small>
        </div>
        <div className="prof-table-wrap">
          <table className="prof-table">
            <thead><tr><th>Learner</th><th>Synthetic ID</th><th>Status</th><th>Submitted</th><th><span className="sr-only">Actions</span></th></tr></thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td><strong>{row.firstName} {row.lastName}</strong></td>
                  <td>{row.id}</td>
                  <td><StatusBadge tone={row.status === 'Submitted' ? 'success' : row.status === 'In progress' ? 'info' : 'neutral'}>{row.status}</StatusBadge></td>
                  <td>{row.submittedAt}</td>
                  <td><button className="prof-text-button" type="button" onClick={() => setSelected(row)}>Details</button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
      {selected ? (
        <section className="prof-detail-drawer" role="dialog" aria-modal="true" aria-labelledby="attempt-detail-title">
          <button className="prof-detail-drawer__backdrop" type="button" aria-label="Close attempt details" onClick={() => setSelected(null)} />
          <div>
            <button className="icon-button" type="button" aria-label="Close attempt details" onClick={() => setSelected(null)}><Icon name="close" /></button>
            <p className="section-kicker">Synthetic attempt detail</p>
            <h2 id="attempt-detail-title">{selected.firstName} {selected.lastName}</h2>
            <dl>
              <div><dt>Status</dt><dd>{selected.status}</dd></div>
              <div><dt>Student ID</dt><dd>{selected.id}</dd></div>
              <div><dt>Submitted</dt><dd>{selected.submittedAt}</dd></div>
              <div><dt>Connection</dt><dd>Prototype snapshot only</dd></div>
            </dl>
            <div className="prof-warning-note"><Icon name="shield" size={18} /><p>No answer content, device monitoring, or intervention action is simulated.</p></div>
          </div>
        </section>
      ) : null}
      <div className="prof-form-actions prof-form-actions--standalone">
        <span />
        <button className="button button--primary" type="button" onClick={() => onNavigate(screens.results)}>Open results <Icon name="arrowRight" size={17} /></button>
      </div>
    </>
  )
}

function Results({ onNavigate }) {
  const [sortMode, setSortMode] = useState('alphabetical')
  const [query, setQuery] = useState('')
  const rows = useMemo(() => {
    const filtered = submissionRows.filter(
      (row) =>
        row.score !== null &&
        `${row.firstName} ${row.lastName} ${row.id}`
          .toLowerCase()
          .includes(query.toLowerCase()),
    )
    return [...filtered].sort((a, b) => {
      if (sortMode === 'ranked') return b.score - a.score || a.lastName.localeCompare(b.lastName) || a.id.localeCompare(b.id)
      return a.lastName.localeCompare(b.lastName) || a.firstName.localeCompare(b.firstName) || a.id.localeCompare(b.id)
    })
  }, [query, sortMode])
  return (
    <>
      <ScreenHeader
        eyebrow="Alphabetical and ranked results"
        title="Results"
        description="Compare deterministic presentation modes using synthetic scores. Result release and tie policy remain unapproved."
        actions={<button className="button button--primary" type="button" onClick={() => onNavigate(screens.exports)}><Icon name="download" size={17} /> Export view</button>}
      />
      <section className="prof-results-summary" aria-label="Result summary">
        <div><span>Submissions</span><strong>27 / 32</strong><small>84% complete</small></div>
        <div><span>Synthetic average</span><strong>84%</strong><small>16.8 / 20 points</small></div>
        <div><span>Score range</span><strong>70–95%</strong><small>Submitted attempts</small></div>
        <div><span>Release state</span><strong>Withheld</strong><small>Policy not approved</small></div>
      </section>
      <section className="prof-panel prof-table-panel">
        <div className="prof-table-toolbar">
          <label className="prof-search"><span className="sr-only">Search results</span><Icon name="search" size={17} /><input placeholder="Search synthetic learner" value={query} onChange={(event) => setQuery(event.target.value)} /></label>
          <div className="prof-sort-toggle" aria-label="Results order">
            <button className={sortMode === 'alphabetical' ? 'active' : ''} type="button" onClick={() => setSortMode('alphabetical')}>Alphabetical</button>
            <button className={sortMode === 'ranked' ? 'active' : ''} type="button" onClick={() => setSortMode('ranked')}>Ranked</button>
          </div>
        </div>
        <div className="prof-results-context"><Icon name="info" size={16} /><span>{sortMode === 'alphabetical' ? 'Sorted by last name, first name, then synthetic ID.' : 'Sorted by score descending; alphabetical order is only a stable display tie-breaker.'}</span></div>
        <div className="prof-table-wrap">
          <table className="prof-table prof-results-table">
            <thead><tr><th>{sortMode === 'ranked' ? 'Place' : '#'}</th><th>Learner</th><th>Synthetic ID</th><th>Submitted</th><th>Score</th><th>Percentage</th><th>Release</th></tr></thead>
            <tbody>
              {rows.map((row, index) => {
                const displayRank =
                  sortMode === 'ranked'
                    ? rows.findIndex((item) => item.score === row.score) + 1
                    : index + 1
                return (
                  <tr key={row.id} data-testid="result-row">
                    <td><span className="prof-rank">{displayRank}</span></td>
                    <td><strong>{row.lastName}, {row.firstName}</strong></td>
                    <td>{row.id}</td><td>{row.submittedAt}</td><td><strong>{row.score} / {row.total}</strong></td><td>{Math.round((row.score / row.total) * 100)}%</td><td><StatusBadge tone="warning">Withheld</StatusBadge></td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </section>
    </>
  )
}

function Exports({ onToast }) {
  const [format, setFormat] = useState('PDF')
  const [history, setHistory] = useState([
    { id: 'exp-1', format: 'PDF', order: 'Alphabetical', generated: '22 Oct · 3:42 PM', status: 'Mock ready' },
  ])
  function generate() {
    setHistory((current) => [
      { id: `exp-${current.length + 1}`, format, order: 'Alphabetical', generated: 'Just now', status: 'Mock ready' },
      ...current,
    ])
    onToast(`${format} export simulated. No file or student data was created.`)
  }
  return (
    <>
      <ScreenHeader
        eyebrow="PDF and Word export UI"
        title="PDF and Word exports"
        description="Preview a shared report contract. This phase does not generate or download actual files."
      />
      <div className="prof-export-layout">
        <section className="prof-panel prof-export-builder" aria-labelledby="export-builder-title">
          <div className="prof-panel-heading"><div><p className="section-kicker">New mock export</p><h2 id="export-builder-title">Configure report</h2></div><StatusBadge tone="info">Simulation</StatusBadge></div>
          <fieldset className="prof-format-options">
            <legend>File format</legend>
            {[
              ['PDF', 'Fixed-layout report preview', 'fileText'],
              ['Word', 'Editable DOCX report preview', 'fileText'],
            ].map(([value, detail, icon]) => (
              <label className={format === value ? 'prof-format-option prof-format-option--selected' : 'prof-format-option'} key={value}>
                <input type="radio" name="export-format" value={value} checked={format === value} onChange={() => setFormat(value)} />
                <span><Icon name={icon} /></span>
                <span><strong>{value}</strong><small>{detail}</small></span>
                <Icon name="checkCircle" size={18} />
              </label>
            ))}
          </fieldset>
          <div className="prof-field-grid">
            <label className="prof-field"><span>Result order</span><select defaultValue="Alphabetical"><option>Alphabetical</option><option>Ranked by score</option></select></label>
            <label className="prof-field"><span>Attempt status</span><select defaultValue="Submitted only"><option>Submitted only</option><option>All roster statuses</option></select></label>
            <label className="prof-check-field prof-field--wide"><input type="checkbox" defaultChecked /><span><strong>Include synthetic student numbers</strong><small>Emails are excluded by default.</small></span></label>
          </div>
          <button className="button button--primary button--full" type="button" onClick={generate}><Icon name="download" size={17} /> Simulate {format} export</button>
        </section>
        <aside className="prof-report-preview">
          <div className="prof-report-paper">
            <div className="prof-report-brand"><span>M</span><div><strong>MARKADO</strong><small>ASSESSMENT RESULTS</small></div></div>
            <h2>Ecosystems checkpoint</h2>
            <p>General Biology · BSBIO 1-A · Synthetic report</p>
            <dl><div><dt>Order</dt><dd>Alphabetical</dd></div><div><dt>Generated</dt><dd>22 Oct 2026 · Asia/Manila</dd></div></dl>
            <table><thead><tr><th>Learner</th><th>Status</th><th>Score</th></tr></thead><tbody><tr><td>Aquino, Gabriel</td><td>Submitted</td><td>17 / 20</td></tr><tr><td>Cruz, Carla</td><td>Submitted</td><td>19 / 20</td></tr><tr><td>Lim, Diego</td><td>Submitted</td><td>18 / 20</td></tr></tbody></table>
            <footer>Prototype preview · Page 1 of 1</footer>
          </div>
        </aside>
      </div>
      <section className="prof-panel prof-export-history" aria-labelledby="export-history-title">
        <div className="prof-panel-heading"><div><p className="section-kicker">Prototype history</p><h2 id="export-history-title">Recent mock exports</h2></div></div>
        {history.map((item) => (
          <div key={item.id}><span className="prof-file-icon"><Icon name="fileText" size={18} /></span><span><strong>{item.format} · Ecosystems checkpoint</strong><small>{item.order} · {item.generated}</small></span><StatusBadge tone="success">{item.status}</StatusBadge><button className="button button--secondary button--compact" type="button" onClick={() => onToast('Download is intentionally unavailable in Phase 2.')}>Preview only</button></div>
        ))}
      </section>
    </>
  )
}

function ProfessorHelp({ onNavigate }) {
  const [query, setQuery] = useState('')
  const topics = professorHelpTopics.filter((topic) => `${topic.title} ${topic.summary}`.toLowerCase().includes(query.toLowerCase()))
  return (
    <>
      <ScreenHeader
        eyebrow="Professor help and guide"
        title="How can we help?"
        description="Find task guidance, prototype boundaries, and clear routes back to the relevant workspace."
      />
      <section className="prof-help-hero">
        <label className="prof-help-search"><Icon name="search" /><span className="sr-only">Search professor guide</span><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search quiz building, AI review, timers, results…" /></label>
        <div><Icon name="shield" size={19} /><p><strong>Prototype support only.</strong> Production account recovery, policy exceptions, and live student support are not available.</p></div>
      </section>
      <div className="prof-help-layout">
        <section className="prof-help-topics" aria-label="Professor guide topics">
          {topics.map((topic, index) => (
            <details key={topic.title} open={index === 0 && !query}>
              <summary><span>{String(index + 1).padStart(2, '0')}</span><span><strong>{topic.title}</strong><small>{topic.summary}</small></span><Icon name="chevronDown" size={18} /></summary>
              <ol>{topic.steps.map((step) => <li key={step}>{step}</li>)}</ol>
            </details>
          ))}
          {!topics.length ? <div className="prof-empty"><Icon name="search" size={28} /><h2>No guide topic found.</h2><p>Try “timer”, “AI”, “results”, or “quiz”.</p></div> : null}
        </section>
        <aside className="prof-help-shortcuts">
          <p className="section-kicker">Quick routes</p><h2>Open a workspace</h2>
          {[
            [screens.create, 'Build a quiz', 'plus'],
            [screens.aiReview, 'Review AI drafts', 'spark'],
            [screens.monitoring, 'Monitor submissions', 'users'],
            [screens.results, 'Review results', 'barChart'],
          ].map(([id, label, icon]) => <button type="button" key={id} onClick={() => onNavigate(id)}><Icon name={icon} size={17} /><span>{label}</span><Icon name="chevronRight" size={16} /></button>)}
          <div><strong>Need a policy decision?</strong><p>Escalate open academic, identity, privacy, or retention choices to the authorized reviewer before production implementation.</p></div>
        </aside>
      </div>
    </>
  )
}

export default function ProfessorApp({ onAdmin, onStudent }) {
  const [screen, setScreen] = useState(screens.dashboard)
  const [mobileNavOpen, setMobileNavOpen] = useState(false)
  const [toast, setToast] = useState('')
  const [draft, setDraft] = useState({
    title: 'Ecosystems checkpoint',
    course: 'General Biology',
    section: 'BSBIO 1-A (synthetic)',
    instructions:
      'Read each item carefully. The display-only prototype timer is 25 minutes. Result release and expiry behavior remain subject to approved policy.',
  })
  const [questions, setQuestions] = useState(() =>
    professorQuestions.map((question) => ({
      ...question,
      choices: [...question.choices],
    })),
  )
  const [drafts, setDrafts] = useState(() =>
    generatedDrafts.map((item) => ({ ...item, choices: [...item.choices] })),
  )
  const [settings, setSettings] = useState({
    opens: '2026-10-22T16:00',
    closes: '2026-10-22T17:00',
    timezone: 'Asia/Manila (UTC+08:00)',
    duration: '25',
    perQuestion: false,
  })
  const [randomization, setRandomization] = useState({
    questions: true,
    choices: true,
    protected: true,
  })
  const [published, setPublished] = useState(false)

  useEffect(() => {
    window.scrollTo(0, 0)
  }, [screen])

  useEffect(() => {
    if (!toast) return undefined
    const timer = window.setTimeout(() => setToast(''), 3200)
    return () => window.clearTimeout(timer)
  }, [toast])

  function navigate(nextScreen) {
    setScreen(nextScreen)
    setMobileNavOpen(false)
  }

  function updateDraft(field, value) {
    setDraft((current) => ({ ...current, [field]: value }))
  }

  let content
  switch (screen) {
    case screens.quizzes:
      content = <QuizManagement onNavigate={navigate} onToast={setToast} />
      break
    case screens.create:
      content = <CreateQuiz draft={draft} onDraftChange={updateDraft} onNavigate={navigate} onToast={setToast} />
      break
    case screens.editor:
      content = <QuestionEditor questions={questions} setQuestions={setQuestions} onNavigate={navigate} onToast={setToast} />
      break
    case screens.aiWorkspace:
      content = <AiWorkspace onNavigate={navigate} onToast={setToast} />
      break
    case screens.aiReview:
      content = <AiReview drafts={drafts} setDrafts={setDrafts} onNavigate={navigate} onToast={setToast} />
      break
    case screens.schedule:
      content = <ScheduleSettings settings={settings} setSettings={setSettings} onNavigate={navigate} onToast={setToast} />
      break
    case screens.randomization:
      content = <RandomizationSettings randomization={randomization} setRandomization={setRandomization} onNavigate={navigate} />
      break
    case screens.preview:
      content = <QuizPreview draft={draft} questions={questions} settings={settings} onNavigate={navigate} />
      break
    case screens.publish:
      content = <Publishing drafts={drafts} published={published} setPublished={setPublished} onNavigate={navigate} onToast={setToast} />
      break
    case screens.monitoring:
      content = <SubmissionMonitoring onNavigate={navigate} onToast={setToast} />
      break
    case screens.results:
      content = <Results onNavigate={navigate} />
      break
    case screens.exports:
      content = <Exports onToast={setToast} />
      break
    case screens.help:
      content = <ProfessorHelp onNavigate={navigate} />
      break
    default:
      content = <ProfessorDashboard onNavigate={navigate} />
  }

  return (
    <div className="prof-app">
      <a className="skip-link" href="#main-content">Skip to main content</a>
      <ProfessorHeader
        currentScreen={screen}
        onAdmin={onAdmin}
        onMenu={() => setMobileNavOpen(true)}
        onStudent={onStudent}
      />
      <div className="prof-frame">
        <ProfessorSidebar
          currentScreen={screen}
          mobileOpen={mobileNavOpen}
          onClose={() => setMobileNavOpen(false)}
          onNavigate={navigate}
        />
        <main className="prof-main" id="main-content">
          {content}
          <footer className="prof-footer">
            <span>MARKADO Professor prototype · Phase 2</span>
            <span>Synthetic data · mock actions · policy-aware UI</span>
          </footer>
        </main>
      </div>
      {toast ? <div className="prof-toast" role="status"><Icon name="checkCircle" size={18} />{toast}</div> : null}
    </div>
  )
}
