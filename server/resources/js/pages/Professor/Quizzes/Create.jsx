import { Link, useForm } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import ProfessorLayout from '../../../Layouts/ProfessorLayout.jsx'
import { FieldError, PolicyBanner, ScreenHeader, StatusBadge } from '../../../Components/ProfessorUi.jsx'

export default function CreateQuiz({ displayTimezone }) {
  const form = useForm({
    title: '',
    course_code: '',
    course_name: '',
    instructions: '',
  })

  function submit(event) {
    event.preventDefault()
    form.post('/professor/quizzes')
  }

  return (
    <ProfessorLayout title="Create quiz">
      <PolicyBanner />
      <ScreenHeader
        eyebrow="Create quiz wizard"
        title="Create a new quiz"
        description="Add the quiz details now, then build and review the question set."
      />

      <nav className="prof-builder-steps" aria-label="Quiz builder steps">
        {['Identity', 'Questions', 'Rules', 'Preview', 'Publish'].map((label, index) => (
          <span className={index === 0 ? 'prof-builder-step prof-builder-step--active' : 'prof-builder-step prof-builder-step--locked'} key={label}>
            <span>{index + 1}</span>
            <strong>{label}</strong>
          </span>
        ))}
      </nav>

      <div className="prof-form-layout">
        <form className="prof-panel prof-form-card" onSubmit={submit} noValidate>
          <div className="prof-panel-heading">
            <div>
              <p className="section-kicker">Step 1 of 5</p>
              <h2>Quiz identity</h2>
            </div>
            <StatusBadge tone="warning">Private draft</StatusBadge>
          </div>
          <div className="prof-field-grid">
            <label className="prof-field prof-field--wide">
              <span>Quiz title</span>
              <input
                value={form.data.title}
                onChange={(event) => form.setData('title', event.target.value)}
                aria-invalid={Boolean(form.errors.title)}
                autoFocus
              />
              <small>Use a clear student-facing title (3–160 characters).</small>
              <FieldError message={form.errors.title} />
            </label>
            <label className="prof-field">
              <span>Course code</span>
              <input
                value={form.data.course_code}
                onChange={(event) => form.setData('course_code', event.target.value)}
                aria-invalid={Boolean(form.errors.course_code)}
                placeholder="BIO 104"
              />
              <FieldError message={form.errors.course_code} />
            </label>
            <label className="prof-field">
              <span>Course name <small>(optional)</small></span>
              <input
                value={form.data.course_name}
                onChange={(event) => form.setData('course_name', event.target.value)}
                aria-invalid={Boolean(form.errors.course_name)}
                placeholder="General Biology"
              />
              <FieldError message={form.errors.course_name} />
            </label>
            <label className="prof-field prof-field--wide">
              <span>Student-facing instructions <small>(optional)</small></span>
              <textarea
                rows="5"
                value={form.data.instructions}
                onChange={(event) => form.setData('instructions', event.target.value)}
                aria-invalid={Boolean(form.errors.instructions)}
                placeholder="Tell students what to prepare and what to expect before they begin."
              />
              <FieldError message={form.errors.instructions} />
            </label>
          </div>
          <div className="prof-form-actions">
            <Link className="button button--secondary" href="/professor/quizzes">Cancel</Link>
            <button className="button button--primary" type="submit" disabled={form.processing}>
              {form.processing ? 'Creating…' : 'Create draft and continue'}
              <Icon name="arrowRight" size={18} />
            </button>
          </div>
        </form>
        <aside className="prof-side-card">
          <Icon name="shield" size={22} />
          <h2>Private by default</h2>
          <p>Your new quiz stays visible only to you until you choose to publish it.</p>
          <ul>
            <li>Display timezone: {displayTimezone}</li>
            <li>Students cannot open a draft.</li>
            <li>You can add the schedule, timers, questions, and correct answers next.</li>
          </ul>
        </aside>
      </div>
    </ProfessorLayout>
  )
}
