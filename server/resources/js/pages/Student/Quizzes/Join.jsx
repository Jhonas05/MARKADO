import { useForm } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Join({ policyNotice }) {
  const form = useForm({ code: '' })

  function submit(event) {
    event.preventDefault()
    form.post('/student/quizzes/preflight', { preserveScroll: true })
  }

  return (
    <StudentQuizLayout title="Join a quiz">
      <main className="student-preflight-shell" id="main-content">
        <section className="student-preflight-hero">
          <p className="section-kicker">Secure join preflight</p>
          <h1>Join a quiz</h1>
          <p>Use the eight-character code supplied by your instructor. Signing in does not create an attempt or start a timer.</p>
        </section>

        <div className="student-preflight-grid">
          <form className="panel student-join-card" onSubmit={submit} noValidate>
            <span className="student-join-card__icon"><Icon name="lock" size={24} /></span>
            <div>
              <label htmlFor="quiz-code">Quiz code</label>
              <p id="quiz-code-help">Letters and numbers only. Codes are not case-sensitive.</p>
            </div>
            <input
              id="quiz-code"
              name="code"
              value={form.data.code}
              onChange={(event) => form.setData('code', event.target.value.toUpperCase())}
              aria-describedby="quiz-code-help quiz-code-error"
              aria-invalid={form.errors.code ? 'true' : undefined}
              autoComplete="off"
              autoCapitalize="characters"
              maxLength={8}
              placeholder="ABCD2345"
            />
            {form.errors.code ? <span className="auth-error" id="quiz-code-error">{form.errors.code}</span> : null}
            <button className="button button--primary" type="submit" disabled={form.processing}>
              {form.processing ? 'Checking securely…' : 'Check quiz access'}
              <Icon name="arrowRight" size={18} />
            </button>
          </form>

          <aside className="panel student-policy-card" role="note">
            <span className="provisional-tag"><Icon name="alertCircle" size={17} /> Policy hold <strong>Phase 5C</strong></span>
            <h2>No assessment starts here</h2>
            <p>{policyNotice}</p>
            <ul>
              <li>No attempt is created.</li>
              <li>No timer is started.</li>
              <li>No question or answer data is sent.</li>
            </ul>
          </aside>
        </div>
      </main>
    </StudentQuizLayout>
  )
}
