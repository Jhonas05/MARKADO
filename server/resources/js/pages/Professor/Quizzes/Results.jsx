import { Link, router, usePage } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import ProfessorLayout from '../../../Layouts/ProfessorLayout.jsx'
import { ScreenHeader, StatusBadge } from '../../../Components/ProfessorUi.jsx'

export default function QuizResults({ attempts, quiz }) {
  const { errors } = usePage().props

  function release(attempt) {
    if (!window.confirm(`Release ${attempt.earnedPoints}/${attempt.maximumPoints} points to ${attempt.studentName}?`)) return
    router.post(`/professor/quizzes/${quiz.id}/attempts/${attempt.id}/release`, {}, { preserveScroll: true })
  }

  return (
    <ProfessorLayout title="Quiz results">
      <ScreenHeader
        eyebrow={`${quiz.courseCode} · Manual release`}
        title={quiz.title}
        description="Review submitted scores and choose when each student can see earned and maximum points."
        actions={<Link className="button button--secondary" href={`/professor/quizzes/${quiz.id}/edit`}><Icon name="arrowLeft" size={18} /> Quiz builder</Link>}
      />

      {errors?.release ? <p className="prof-validation-summary" role="alert">{errors.release}</p> : null}
      <section className="prof-panel professor-results" aria-labelledby="results-title">
        <div className="prof-panel-heading">
          <div><p className="section-kicker">Owned submissions</p><h2 id="results-title">Scoring and release status</h2></div>
          <StatusBadge>{attempts.length} records</StatusBadge>
        </div>
        <div className="professor-results__table-wrap">
          <table>
            <thead><tr><th>Student</th><th>Status</th><th>Score</th><th>Release</th><th><span className="sr-only">Action</span></th></tr></thead>
            <tbody>
              {attempts.map((attempt) => (
                <tr key={attempt.id}>
                  <td><strong>{attempt.studentName}</strong><small>{attempt.studentNumber || 'School record unavailable'}</small></td>
                  <td>{attempt.status}</td>
                  <td>{attempt.scoringStatus === 'scored' ? `${attempt.earnedPoints} / ${attempt.maximumPoints}` : attempt.scoringStatus}</td>
                  <td>{attempt.releasedAt ? <StatusBadge tone="success">Released</StatusBadge> : <StatusBadge tone="warning">Withheld</StatusBadge>}</td>
                  <td><button className="button button--secondary button--compact" type="button" disabled={!attempt.canRelease} onClick={() => release(attempt)}>{attempt.releasedAt ? 'Released' : 'Release'}</button></td>
                </tr>
              ))}
              {!attempts.length ? <tr><td colSpan="5">No assessment attempts have been recorded.</td></tr> : null}
            </tbody>
          </table>
        </div>
        <p className="assessment-policy-note">Additional reporting tools, including rankings, bulk release, regrading, and exports, are not available yet.</p>
      </section>
    </ProfessorLayout>
  )
}
