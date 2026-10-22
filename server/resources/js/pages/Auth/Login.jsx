import { Head, Link, useForm } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'

export default function Login({ registration }) {
  const form = useForm({
    email: '',
    password: '',
  })

  function submit(event) {
    event.preventDefault()
    form.post('/login', {
      onFinish: () => form.reset('password'),
    })
  }

  return (
    <div className="auth-page">
      <Head title="Sign in" />
      <a className="skip-link" href="#login-form">
        Skip to sign in
      </a>

      <main className="auth-shell">
        <section className="auth-story" aria-labelledby="auth-heading">
          <Link className="auth-brand" href="/" aria-label="MARKADO prototype home">
            <BrandMark size={48} />
            <span>MARKADO</span>
          </Link>

          <div>
            <p className="section-kicker">Secure assessment foundation</p>
            <h1 id="auth-heading">A focused place for every assessment.</h1>
            <p>
              This sign-in foundation uses Laravel sessions, server validation, and
              protected Inertia routes while academic account policies remain under review.
            </p>
          </div>

          <div className="auth-boundary" role="note">
            <strong>Phase 5A boundary</strong>
            <span>No quiz, grading, timing, or eligibility policy is activated here.</span>
          </div>
        </section>

        <section className="auth-card" aria-labelledby="login-title">
          <div className="auth-card__heading">
            <p className="section-kicker">Provisioned accounts</p>
            <h2 id="login-title">Sign in to your workspace</h2>
            <p>Use an account supplied through an approved development or institution workflow.</p>
          </div>

          <form id="login-form" onSubmit={submit} noValidate>
            <div className="auth-field">
              <label htmlFor="email">Email address</label>
              <input
                id="email"
                name="email"
                type="email"
                autoComplete="username"
                value={form.data.email}
                aria-invalid={Boolean(form.errors.email)}
                aria-describedby={form.errors.email ? 'email-error' : undefined}
                onChange={(event) => form.setData('email', event.target.value)}
              />
              {form.errors.email && (
                <span className="auth-error" id="email-error" role="alert">
                  {form.errors.email}
                </span>
              )}
            </div>

            <div className="auth-field">
              <label htmlFor="password">Password</label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                value={form.data.password}
                aria-invalid={Boolean(form.errors.password)}
                aria-describedby={form.errors.password ? 'password-error' : undefined}
                onChange={(event) => form.setData('password', event.target.value)}
              />
              {form.errors.password && (
                <span className="auth-error" id="password-error" role="alert">
                  {form.errors.password}
                </span>
              )}
            </div>

            <button className="button button--primary auth-submit" type="submit" disabled={form.processing}>
              {form.processing ? 'Signing in…' : 'Sign in'}
            </button>
          </form>

          <div className="auth-registration-note" role="note">
            <strong>Registration is not open.</strong>
            <span>{registration.message}</span>
          </div>

          <Link className="auth-back-link" href="/">
            Return to the synthetic Student prototype
          </Link>
        </section>
      </main>
    </div>
  )
}
