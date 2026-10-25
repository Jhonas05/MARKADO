import { Head, Link, usePage } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Welcome({ accountPolicies }) {
  const user = usePage().props.auth.user

  return (
    <div className="welcome-page">
      <Head title="Secure academic assessments" />
      <a className="skip-link" href="#main-content">Skip to content</a>

      <header className="welcome-header">
        <Link className="auth-brand" href="/" aria-label="MARKADO home">
          <MarkadoLogo decorative size={28} />
        </Link>
        <div className="welcome-header__actions">
          <ThemeToggle compact />
          <Link className="button button--primary" href={user ? '/workspace' : '/login'}>
            {user ? 'Open workspace' : 'Sign in'}
          </Link>
        </div>
      </header>

      <main id="main-content">
        <section className="welcome-hero" aria-labelledby="welcome-title">
          <div>
            <p className="section-kicker">Secure academic assessment</p>
            <h1 id="welcome-title">Focus on the work. Trust the assessment.</h1>
            <p>
              MARKADO gives students, professors, and academic administrators one
              secure place to prepare, take, and manage assessments.
            </p>
            <div className="welcome-hero__actions">
              <Link className="button button--primary" href={user ? '/workspace' : '/login'}>
                {user ? 'Continue to workspace' : 'Sign in to MARKADO'}
                <Icon name="arrowRight" size={17} />
              </Link>
              <Link className="button button--secondary" href="/register">
                Student registration status
              </Link>
            </div>
          </div>

          <aside className="welcome-security-card" aria-label="Access boundaries">
            <p className="section-kicker">Access by assignment</p>
            <h2>Your account opens the right workspace.</h2>
            <ul>
              <li><Icon name="shield" size={18} /> Role-specific permissions</li>
              <li><Icon name="lock" size={18} /> Protected sign-in sessions</li>
              <li><Icon name="clock" size={18} /> Consistent assessment deadlines</li>
            </ul>
          </aside>
        </section>

        <section className="welcome-boundaries" aria-labelledby="access-heading">
          <div>
            <p className="section-kicker">Account access</p>
            <h2 id="access-heading">Institution-managed by design.</h2>
          </div>
          <div className="welcome-boundaries__grid">
            <article>
              <Icon name="user" size={20} />
              <h3>Students</h3>
              <p>Student access requires a school-issued email, official student number, and a matching school record.</p>
              <span>{accountPolicies.publicStudentRegistration ? 'Registration open' : 'Registration unavailable'}</span>
            </article>
            <article>
              <Icon name="book" size={20} />
              <h3>Professors</h3>
              <p>Professor access is assigned by the school and cannot be selected on the sign-in page.</p>
              <span>{accountPolicies.publicStaffRegistration ? 'Open' : 'Provisioned accounts only'}</span>
            </article>
            <article>
              <Icon name="shield" size={20} />
              <h3>Administrators</h3>
              <p>Administrator access is limited to the permissions assigned to each account.</p>
              <span>Least privilege</span>
            </article>
          </div>
        </section>
      </main>
    </div>
  )
}
