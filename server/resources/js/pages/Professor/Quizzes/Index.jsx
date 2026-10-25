import { Link } from '@inertiajs/react'
import Icon from '@prototype/components/Icon.jsx'
import ProfessorLayout from '../../../Layouts/ProfessorLayout.jsx'
import { PolicyBanner, ScreenHeader, StatusBadge } from '../../../Components/ProfessorUi.jsx'

function formatDate(value) {
  if (!value) return 'Not yet saved'

  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Asia/Manila',
  }).format(new Date(value))
}

export default function QuizIndex({ quizzes }) {
  return (
    <ProfessorLayout title="My quizzes">
      <PolicyBanner />
      <ScreenHeader
        eyebrow="Quiz management"
        title="My quizzes"
        description="Create, publish, and review quizzes that belong to your account."
        actions={(
          <Link className="button button--primary" href="/professor/quizzes/create">
            <Icon name="plus" size={18} />
            New quiz
          </Link>
        )}
      />

      {quizzes.length ? (
        <section className="prof-quiz-grid" aria-label="Quiz drafts">
          {quizzes.map((quiz) => (
            <article className="prof-quiz-card" key={quiz.id}>
              <div className="prof-quiz-card__top">
                <span className="prof-course-tile">{quiz.courseCode.slice(0, 3).toUpperCase()}</span>
                <StatusBadge tone={quiz.status === 'published' ? 'success' : 'warning'}>{quiz.statusLabel}</StatusBadge>
              </div>
              <p>{quiz.courseCode}{quiz.courseName ? ` · ${quiz.courseName}` : ''}</p>
              <h2>{quiz.title}</h2>
              <dl>
                <div>
                  <dt>Questions</dt>
                  <dd>{quiz.questionCount}</dd>
                </div>
                <div>
                  <dt>Attempts</dt>
                  <dd>{quiz.attemptCount}</dd>
                </div>
                <div>
                  <dt>Access</dt>
                  <dd>{quiz.status === 'published' ? 'Published' : 'Private'}</dd>
                </div>
              </dl>
              <div className="prof-quiz-card__footer">
                <small>Updated {formatDate(quiz.updatedAt)}</small>
                <Link className="button button--secondary button--compact" href={`/professor/quizzes/${quiz.id}/edit`}>
                  Manage
                </Link>
              </div>
            </article>
          ))}
        </section>
      ) : (
        <section className="prof-panel prof-empty" role="status">
          <Icon name="book" size={30} />
          <h2>No quizzes yet</h2>
          <p>Create your first quiz, then add multiple-choice or True/False questions.</p>
          <Link className="button button--primary prof-empty__action" href="/professor/quizzes/create">
            Create a quiz
          </Link>
        </section>
      )}
    </ProfessorLayout>
  )
}
