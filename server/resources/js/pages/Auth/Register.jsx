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
            Students will register with an official student number and school-issued email. Access opens only after mailbox and school-record checks succeed.
          </p>

          <form className="auth-form" aria-describedby="registration-policy registration-privacy">
            <fieldset disabled={!registration.available}>
              <legend className="sr-only">Student onboarding fields</legend>
              <div className="auth-field">
                <label htmlFor="student-number">Official student number</label>
                <input id="student-number" name="student_number" autoComplete="off" placeholder="Preserved exactly as issued" aria-describedby="student-number-hint" />
                <p className="auth-field__hint" id="student-number-hint">This must match the number on the trusted school roster.</p>
              </div>
              <div className="auth-field">
                <label htmlFor="registration-email">Institutional email address</label>
                <input id="registration-email" name="email" type="email" autoComplete="email" placeholder="School-issued mailbox only" aria-describedby="registration-email-hint" />
                <p className="auth-field__hint" id="registration-email-hint">Personal email and lookalike domains are not accepted.</p>
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
            <button className="button button--primary button--full" type="button" disabled={!registration.available}>
              {registration.available ? 'Continue securely' : 'Registration unavailable'}
            </button>
            <p className="auth-validation-status" aria-live="polite">No information has been submitted.</p>
          </form>

          <section className="identity-requirements" aria-labelledby="identity-requirements-title">
            <h2 id="identity-requirements-title">Required checks</h2>
            <ul>
              {registration.requirements.map((requirement) => <li key={requirement}>{requirement}</li>)}
            </ul>
          </section>

          <section className="identity-requirements" aria-labelledby="verification-states-title">
            <h2 id="verification-states-title">Verification states</h2>
            <ul>
              {registration.verificationStates.map((state) => <li key={state}>{state}</li>)}
            </ul>
            <p className="auth-field__hint">Generic resend acknowledgement: {registration.resendAcknowledgement}</p>
          </section>

          <div className="auth-registration-note" id="registration-policy" role="note">
            <strong>Registration is not available yet</strong>
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
