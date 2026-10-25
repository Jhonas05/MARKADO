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
          <h1 id="registration-title">Create a student account</h1>
          <p className="auth-card__intro">
            The required fields are shown for design and validation review. Submission is disabled until institutional verification authority is approved.
          </p>

          <form className="auth-form" aria-describedby="registration-policy">
            <div className="auth-field">
              <label htmlFor="student-number">Student number</label>
              <input id="student-number" name="student_number" autoComplete="off" disabled placeholder="Issued by your school" />
            </div>
            <div className="auth-field">
              <label htmlFor="registration-email">School email address</label>
              <input id="registration-email" name="email" type="email" autoComplete="email" disabled placeholder="name@school.edu" />
            </div>
            <div className="auth-field">
              <label htmlFor="registration-password">Password</label>
              <input id="registration-password" name="password" type="password" autoComplete="new-password" disabled />
            </div>
            <button className="button button--primary button--full" type="button" disabled>
              Registration unavailable
            </button>
          </form>

          <div className="auth-registration-note" id="registration-policy" role="note">
            <strong>Why is this disabled?</strong>
            <span>{registration.message}</span>
          </div>
          <p className="auth-card__footer">Already provisioned? <Link href="/login">Sign in</Link></p>
        </section>
      </main>
    </div>
  )
}
