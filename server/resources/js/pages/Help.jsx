import { Head, Link, usePage } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Help({ topics }) {
  const { auth } = usePage().props
  const home = auth.user.role === 'student'
    ? '/student/dashboard'
    : auth.user.role === 'instructor' ? '/professor/quizzes' : '/admin/dashboard'

  return (
    <div className="help-workspace">
      <Head title="Help and guide" />
      <a className="skip-link" href="#main-content">Skip to content</a>
      <header className="auth-topbar">
        <Link className="auth-brand" href={home} aria-label="Return to workspace"><MarkadoLogo decorative size={26} /></Link>
        <div className="auth-topbar__actions">
          <ThemeToggle compact />
          <Link className="button button--secondary" href={home}>Workspace</Link>
          <Link className="button button--secondary" href="/logout" method="delete" as="button">Sign out</Link>
        </div>
      </header>
      <main className="help-workspace__main" id="main-content">
        <header className="workspace-hero">
          <p className="section-kicker">MARKADO guide</p>
          <h1>Help for secure assessment work</h1>
          <p>Short, role-aware guidance for registration, quiz delivery, result release, and account safety.</p>
        </header>
        <section className="help-topic-grid" aria-label="Help topics">
          {topics.map((topic) => (
            <article className="panel help-topic-card" key={topic.title}>
              <h2>{topic.title}</h2>
              <ol>{topic.steps.map((step) => <li key={step}>{step}</li>)}</ol>
            </article>
          ))}
        </section>
      </main>
    </div>
  )
}
