import { Head, Link, useForm } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function Register({ registration }) {
  const form = useForm({ student_number: '', email: '', password: '', password_confirmation: '' })
  const fields = [
    { name: 'student_number', label: 'Official student number', type: 'text', autoComplete: 'off', hint: 'Enter your exact school-issued number, including any leading zeros.' },
    { name: 'email', label: 'Institutional email address', type: 'email', autoComplete: 'email', hint: 'Use the school-issued mailbox that matches your student record.' },
    { name: 'password', label: 'Password', type: 'password', autoComplete: 'new-password', hint: 'At least 12 characters, with uppercase and lowercase letters and a number.' },
    { name: 'password_confirmation', label: 'Confirm password', type: 'password', autoComplete: 'new-password' },
  ]

  function submit(event) {
    event.preventDefault()
    if (!registration.available || form.processing) return
    form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') })
  }

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
          <h1 id="registration-title">{registration.available ? 'Create your Student account' : 'Student onboarding requirements'}</h1>
          <p className="auth-card__intro">
            Use your official student number and school-issued email. Your account opens after email verification and matching school-record checks.
          </p>

          <form className="auth-form" onSubmit={submit} noValidate aria-describedby="registration-policy registration-privacy">
            <fieldset disabled={!registration.available || form.processing}>
              <legend className="sr-only">Student onboarding fields</legend>
              {fields.map(({ name, label, type, autoComplete, hint }) => (
                <div className="auth-field" key={name}>
                  <label htmlFor={`registration-${name}`}>{label}</label>
                  <input id={`registration-${name}`} name={name} type={type} autoComplete={autoComplete} required
                    value={form.data[name]} onChange={(event) => form.setData(name, event.target.value)}
                    aria-invalid={Boolean(form.errors[name])}
                    aria-describedby={[hint && `${name}-hint`, form.errors[name] && `${name}-error`].filter(Boolean).join(' ') || undefined} />
                  {hint && <p className="auth-field__hint" id={`${name}-hint`}>{hint}</p>}
                  {form.errors[name] && <span className="auth-error" id={`${name}-error`} role="alert">{form.errors[name]}</span>}
                </div>
              ))}
            </fieldset>
            <button className="button button--primary button--full" type="submit" disabled={!registration.available || form.processing}>
              {form.processing ? 'Creating account…' : registration.available ? 'Create account' : 'Registration unavailable'}
            </button>
            <p className="auth-validation-status" aria-live="polite">{form.processing ? 'Submitting your details…' : Object.keys(form.errors).length ? 'Check the highlighted fields and try again.' : 'Your school record and mailbox will be checked securely.'}</p>
          </form>

          <section className="identity-requirements" aria-labelledby="identity-requirements-title">
            <h2 id="identity-requirements-title">Required checks</h2>
            <ul>
              {registration.requirements.map((requirement) => <li key={requirement}>{requirement}</li>)}
            </ul>
          </section>

          {!registration.available && <section className="identity-requirements" aria-labelledby="verification-states-title">
            <h2 id="verification-states-title">Verification states</h2>
            <ul>
              {registration.verificationStates.map((state) => <li key={state}>{state}</li>)}
            </ul>
            <p className="auth-field__hint">Generic resend acknowledgement: {registration.resendAcknowledgement}</p>
          </section>}

          <div className="auth-registration-note" id="registration-policy" role="note">
            <strong>{registration.available ? 'Your school identity stays protected' : 'Registration is currently unavailable'}</strong>
            <span>{registration.available ? 'If registration cannot proceed, sign in to an existing account or contact your school. Registration never replaces an existing account.' : registration.message}</span>
          </div>
          <p className="auth-privacy-note" id="registration-privacy">
            Your information is used to verify your school identity. Never share your password or verification code with staff or other students.
          </p>
          <p className="auth-card__footer">Already have an account? <Link href="/login">Sign in</Link></p>
        </section>
      </main>
    </div>
  )
}
