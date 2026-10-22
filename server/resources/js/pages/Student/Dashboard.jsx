import Icon from '@prototype/components/Icon.jsx'
import FoundationNotice from '../../Components/FoundationNotice.jsx'
import MarkadoLayout from '../../Layouts/MarkadoLayout.jsx'

const metricIcons = {
  accent: 'book',
  success: 'checkCircle',
  info: 'trophy',
}

export default function Dashboard({ foundation, metrics, quiz, recentResults, student }) {
  return (
    <MarkadoLayout student={student} title="Student overview">
      <main className="page-shell dashboard-page" id="main-content">
        <FoundationNotice
          framework={foundation.framework}
          persistence={foundation.persistence}
        />

        <section className="dashboard-intro">
          <div>
            <p className="section-kicker">Student overview</p>
            <h1>Welcome back, {student.firstName}.</h1>
            <p className="intro-copy">
              One practice assessment is ready. This screen is now delivered through
              a real Laravel route and Inertia page response.
            </p>
          </div>
          <button className="button button--dark" type="button" disabled>
            Join workflow coming next
            <Icon name="arrowRight" size={18} />
          </button>
        </section>

        <section className="metric-grid" aria-label="Assessment summary">
          {metrics.map((metric) => (
            <article
              className={metric.tone === 'accent' ? 'metric-card metric-card--accent' : 'metric-card'}
              key={metric.label}
            >
              <span
                className={
                  metric.tone === 'success'
                    ? 'metric-card__icon metric-card__icon--green'
                    : metric.tone === 'info'
                      ? 'metric-card__icon metric-card__icon--blue'
                      : 'metric-card__icon'
                }
              >
                <Icon name={metricIcons[metric.tone]} />
              </span>
              <div>
                <strong>{metric.value}</strong>
                <span>{metric.label}</span>
              </div>
              <small>{metric.detail}</small>
            </article>
          ))}
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
                <p className="quiz-feature__course">
                  {quiz.course} · {quiz.courseName}
                </p>
                <h3>{quiz.title}</h3>
                <p className="muted">Prepared by {quiz.instructor}</p>

                <dl className="quiz-meta">
                  <div>
                    <dt><Icon name="calendar" size={17} />Schedule</dt>
                    <dd>{quiz.schedule}</dd>
                  </div>
                  <div>
                    <dt><Icon name="clock" size={17} />Duration</dt>
                    <dd>{quiz.durationMinutes} minutes</dd>
                  </div>
                  <div>
                    <dt><Icon name="book" size={17} />Questions</dt>
                    <dd>{quiz.questionCount} items</dd>
                  </div>
                </dl>

                <button className="button button--primary" type="button" disabled>
                  Instructions pending server workflow
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

        <section className="foundation-guide panel" id="foundation-guide" aria-labelledby="foundation-title">
          <p className="section-kicker">Migration boundary</p>
          <h2 id="foundation-title">What this foundation proves</h2>
          <p>
            Laravel owns this route and supplies safe, synthetic props through Inertia.
            React renders the existing MARKADO design language. Authentication,
            authorization, quiz persistence, authoritative timers, grading, and result
            release are deliberately not implemented until their policies are approved.
          </p>
        </section>
      </main>
    </MarkadoLayout>
  )
}
