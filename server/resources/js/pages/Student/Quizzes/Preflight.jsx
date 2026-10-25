import { Link, router, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import StudentQuizLayout from '../../../Layouts/StudentQuizLayout.jsx'

export default function Preflight({ preflight }) {
  const { errors } = usePage().props
  const available = preflight.state === 'information_available' && preflight.quiz

  function startAssessment() {
    if (!preflight.canStart) return
    router.post(`/student/quizzes/${preflight.quiz.id}/attempts`, {}, { preserveScroll: true })
  }

  return (
    <StudentQuizLayout title="Quiz details">
      <main className="student-preflight-shell" id="main-content">
        <section className="student-preflight-hero">
          <p className="section-kicker">Review before starting</p>
          <h1>{available ? preflight.quiz.title : 'Quiz unavailable'}</h1>
          <p>{preflight.message}</p>
        </section>

        {available ? (
          <div className="student-instructions-grid">
            <section className="panel student-quiz-info" aria-labelledby="quiz-information-title">
              <p className="section-kicker">Quiz details</p>
              <h2 id="quiz-information-title">{preflight.quiz.courseCode}</h2>
              <p className="student-quiz-course">{preflight.quiz.courseName || 'Course name not supplied'}</p>
              <dl>
                <div><dt>Questions</dt><dd>{preflight.quiz.questionCount}</dd></div>
                <div><dt>Display timezone</dt><dd>{preflight.quiz.displayTimezone}</dd></div>
                <div><dt>Opens</dt><dd>{preflight.quiz.opensAt ? new Date(preflight.quiz.opensAt).toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) : 'Not scheduled'}</dd></div>
                <div><dt>Closes</dt><dd>{preflight.quiz.closesAt ? new Date(preflight.quiz.closesAt).toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) : 'Not scheduled'}</dd></div>
                <div><dt>Quiz timer</dt><dd>{preflight.quiz.wholeQuizDurationSeconds ? `${Math.round(preflight.quiz.wholeQuizDurationSeconds / 60)} minutes` : 'Global close only'}</dd></div>
                <div><dt>Question timer</dt><dd>{preflight.quiz.perQuestionDurationSeconds ? `${preflight.quiz.perQuestionDurationSeconds} seconds each` : 'Off'}</dd></div>
              </dl>
            </section>

            <section className="panel student-instructions" aria-labelledby="instructions-title">
              <p className="section-kicker">Professor instructions</p>
              <h2 id="instructions-title">Before you begin</h2>
              <p>{preflight.quiz.instructions || 'No additional instructions were provided.'}</p>
            </section>
          </div>
        ) : (
          <section className="panel student-unavailable" role="status">
            <span><Icon name="lock" size={26} /></span>
            <div>
              <h2>Access remains closed</h2>
              <p>For privacy, MARKADO does not confirm whether an unavailable code belongs to a draft, a restricted quiz, or no quiz.</p>
            </div>
          </section>
        )}

        <section className="student-preflight-actions" aria-label="Preflight actions">
          <Link className="button button--secondary" href="/student/quizzes/join">
            <Icon name="arrowLeft" size={18} /> Try another code
          </Link>
          <button className="button button--primary" type="button" disabled={!preflight.canStart} onClick={startAssessment} aria-describedby="start-policy-note">
            Start quiz
            <Icon name="arrowRight" size={18} />
          </button>
          <p id="start-policy-note">
            {preflight.startMessage || (preflight.canStart
              ? 'Your timer begins only after MARKADO accepts your Start request.'
              : 'Starting this quiz is not available right now.')}
          </p>
          {errors?.assessment ? <p className="auth-error" role="alert">{errors.assessment}</p> : null}
        </section>
      </main>
    </StudentQuizLayout>
  )
}
