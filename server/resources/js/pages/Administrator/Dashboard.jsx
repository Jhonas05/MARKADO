import { Head, Link, usePage } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Dashboard({ boundaries, operations, summary }) {
  const user = usePage().props.auth.user

  return (
    <div className="admin-workspace">
      <Head title="Administrator dashboard" />
      <a className="skip-link" href="#main-content">Skip to content</a>
      <header className="auth-topbar">
        <Link className="auth-brand" href="/admin/dashboard" aria-label="MARKADO administrator dashboard">
          <MarkadoLogo decorative size={26} />
        </Link>
        <div className="auth-topbar__actions"><ThemeToggle compact /><Link className="button button--secondary" href="/help">Help</Link><Link className="button button--secondary" href="/logout" method="delete" as="button">Sign out</Link></div>
      </header>
      <main className="admin-workspace__main" id="main-content">
        <section className="workspace-hero">
          <p className="section-kicker">Academic administration</p>
          <h1>Welcome, {user.name}.</h1>
          <p>Your server-stored administrator role opens only the capabilities explicitly authorized for this account.</p>
        </section>
        <section className="admin-summary-grid" aria-label="Academic operations summary">
          <article className="panel"><span>Active students</span><strong>{summary.activeStudents}</strong></article>
          <article className="panel"><span>Active professors</span><strong>{summary.activeProfessors}</strong></article>
          <article className="panel"><span>Pending students</span><strong>{summary.pendingStudents}</strong></article>
          <article className="panel"><span>Published quizzes</span><strong>{summary.publishedQuizzes}</strong></article>
          <article className="panel"><span>Active attempts</span><strong>{summary.activeAttempts}</strong></article>
          <article className="panel"><span>Submitted attempts</span><strong>{summary.submittedAttempts}</strong></article>
        </section>
        <section className="panel admin-operations" aria-labelledby="operations-title">
          <div><p className="section-kicker">Read-only readiness</p><h2 id="operations-title">Operational gates</h2></div>
          <dl>
            <div><dt>Student registration</dt><dd>{operations.registrationAvailable ? 'Available' : 'Blocked'}</dd></div>
            <div><dt>Production identity readiness</dt><dd>{operations.productionRegistrationReady ? 'Ready' : 'Missing approved dependencies'}</dd></div>
            <div><dt>Assessment routes</dt><dd>{operations.assessmentRoutesEnabled ? 'Enabled' : 'Disabled'}</dd></div>
            <div><dt>Timer reconciliation</dt><dd>{operations.expirationEnabled ? 'Enabled' : 'Disabled'}</dd></div>
            <div><dt>Gamification</dt><dd>{operations.gamificationEnabled ? 'Enabled' : 'Disabled'}</dd></div>
            <div><dt>Live verification challenges</dt><dd>{summary.pendingEmailChallenges}</dd></div>
          </dl>
        </section>
        <div className="admin-boundary-grid">
          {Object.entries(boundaries).map(([key, value]) => (
            <article className="panel admin-boundary-card" key={key}>
              <Icon name="shield" size={20} />
              <h2>{key.replace(/([A-Z])/g, ' $1')}</h2>
              <p>{value}</p>
            </article>
          ))}
        </div>
        <section className="admin-policy-note" role="note">
          Privileged account and assessment changes remain unavailable until the school defines the required authority and audit controls.
        </section>
      </main>
    </div>
  )
}
