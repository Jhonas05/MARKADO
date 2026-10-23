import { Head, Link, usePage } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Dashboard({ boundaries }) {
  const user = usePage().props.auth.user

  return (
    <div className="admin-workspace">
      <Head title="Administrator dashboard" />
      <a className="skip-link" href="#main-content">Skip to content</a>
      <header className="auth-topbar">
        <Link className="auth-brand" href="/admin/dashboard" aria-label="MARKADO administrator dashboard">
          <BrandMark size={34} /><span>MARKADO</span>
        </Link>
        <div className="auth-topbar__actions"><ThemeToggle compact /><Link className="button button--secondary" href="/logout" method="delete" as="button">Sign out</Link></div>
      </header>
      <main className="admin-workspace__main" id="main-content">
        <section className="workspace-hero">
          <p className="section-kicker">Academic administration</p>
          <h1>Welcome, {user.name}.</h1>
          <p>Your server-stored administrator role opens only the capabilities explicitly authorized for this account.</p>
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
          Privileged mutations are intentionally unavailable until D15 and D23 define the exact authority and audit requirements.
        </section>
      </main>
    </div>
  )
}
