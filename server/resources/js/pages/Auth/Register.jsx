import { Head, Link } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Register({ registration }) {
  return (
    <div className="auth-page">
      <Head title="Student registration" />
      <main className="auth-shell auth-shell--single" id="main-content">
        <section className="auth-card auth-card--registration" aria-labelledby="registration-title">
          <div className="auth-card__topline">
            <Link className="auth-brand" href="/" aria-label="MARKADO home">
              <MarkadoLogo decorative size={30} />
            </Link>
            <ThemeToggle compact />
          </div>
          <p className="section-kicker">Student access</p>
          <h1 id="registration-title">Student onboarding requirements</h1>
          <p className="auth-card__intro">
            MARKADO accepts school-issued institutional email only. Your mailbox, student number, and trusted school roster identity must all match before an account can be considered for activation.
          </p>

          <form className="auth-form" aria-describedby="registration-policy registration-privacy">
            <fieldset disabled>
              <legend className="sr-only">Student onboarding fields</legend>
              <div className="auth-field">
                <label htmlFor="student-number">Official student number</label>
                <input id="student-number" name="student_number" autoComplete="off" placeholder="Preserved exactly as issued" />
              </div>
              <div className="auth-field">
                <label htmlFor="registration-email">Institutional email address</label>
                <input id="registration-email" name="email" type="email" autoComplete="email" placeholder="School-issued mailbox only" />
              </div>
              <div className="auth-field">
                <label htmlFor="registration-password">Password</label>
                <input id="registration-password" name="password" type="password" autoComplete="new-password" />
              </div>
              <div className="auth-field">
                <label htmlFor="registration-password-confirmation">Confirm password</label>
                <input id="registration-password-confirmation" name="password_confirmation" type="password" autoComplete="new-password" />
              </div>
            </fieldset>
            <button className="button button--primary button--full" type="button" disabled>
              Registration unavailable
            </button>
            <p className="auth-validation-status" aria-live="polite">No information has been submitted.</p>
          </form>

          <section className="identity-requirements" aria-labelledby="identity-requirements-title">
            <h2 id="identity-requirements-title">Required checks</h2>
            <ul>
              {registration.requirements.map((requirement) => <li key={requirement}>{requirement}</li>)}
            </ul>
          </section>

          <div className="auth-registration-note" id="registration-policy" role="note">
            <strong>Why is this disabled?</strong>
            <span>{registration.message}</span>
          </div>
          <p className="auth-privacy-note" id="registration-privacy">
            Privacy notice: only the minimum identity information needed for school verification should be collected. Do not submit personal email addresses or credentials to staff through this inactive form.
          </p>
          <p className="auth-card__footer">Already provisioned? <Link href="/login">Sign in</Link></p>
        </section>
      </main>
    </div>
  )
}
