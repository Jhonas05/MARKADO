import { Head, Link, usePage } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'

export default function Workspace({ foundation }) {
  const { auth } = usePage().props
  const user = auth.user

  return (
    <div className="auth-page auth-page--workspace">
      <Head title="Authenticated workspace" />
      <a className="skip-link" href="#main-content">
        Skip to content
      </a>

      <header className="auth-topbar">
        <Link className="auth-brand" href="/" aria-label="MARKADO prototype home">
          <BrandMark />
          <span>MARKADO</span>
        </Link>
        <Link className="button button--secondary" href="/logout" method="delete" as="button">
          Sign out
        </Link>
      </header>

      <main className="workspace-shell" id="main-content">
        <section className="workspace-hero">
          <p className="section-kicker">Authenticated foundation</p>
          <h1>Welcome, {user.name}.</h1>
          <p>
            Your secure session is active. Product access stays intentionally limited until
            institution, eligibility, and permission decisions are approved for Phase 5B.
          </p>
        </section>

        <div className="workspace-grid">
          <section className="panel workspace-card" aria-labelledby="identity-title">
            <p className="section-kicker">Safe account props</p>
            <h2 id="identity-title">Session identity</h2>
            <dl>
              <div>
                <dt>Name</dt>
                <dd>{user.name}</dd>
              </div>
              <div>
                <dt>Email</dt>
                <dd>{user.email}</dd>
              </div>
              <div>
                <dt>Role</dt>
                <dd>{user.roleLabel ?? 'Not assigned'}</dd>
              </div>
            </dl>
          </section>

          <section className="panel workspace-card" aria-labelledby="boundary-title">
            <p className="section-kicker">Policy boundary</p>
            <h2 id="boundary-title">What is active</h2>
            <ul>
              <li>Session login, logout, validation, and request throttling</li>
              <li>CSRF-protected state changes and protected routes</li>
              <li>Nullable role architecture with no self-service assignment</li>
              <li>{foundation.persistence}</li>
            </ul>
          </section>
        </div>

        <section className="workspace-status" role="note">
          <span>Database driver</span>
          <strong>{foundation.database}</strong>
          <span>Policy status</span>
          <strong>{foundation.policyStatus}</strong>
        </section>

        {foundation.quizAuthoringUrl ? (
          <Link className="button button--primary workspace-authoring-link" href={foundation.quizAuthoringUrl}>
            Open Professor Quiz Builder
          </Link>
        ) : null}
        {foundation.quizJoinUrl ? (
          <Link className="button button--primary workspace-authoring-link" href={foundation.quizJoinUrl}>
            Open secure quiz preflight
          </Link>
        ) : null}
      </main>
    </div>
  )
}
