import { useState } from 'react'
import { Head, Link, useForm, usePage } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'
import AuthShowcase from '../../Components/AuthShowcase.jsx'

export default function Login({ registration }) {
  const { flash } = usePage().props
  const [showPassword, setShowPassword] = useState(false)
  const form = useForm({ email: '', password: '' })

  function submit(event) {
    event.preventDefault()
    form.post('/login', { onFinish: () => form.reset('password') })
  }

  return (
    <div className="auth-page auth-page--showcase">
      <Head title="Sign in" />
      <a className="skip-link" href="#login-form">Skip to sign in</a>

      <main className="auth-shell auth-shell--showcase">
        <AuthShowcase />

        <section className="auth-workspace" aria-labelledby="login-title">
          <div className="auth-card auth-card--showcase">
            <div className="auth-card__topline">
              <Link className="auth-brand" href="/" aria-label="MARKADO home">
                <MarkadoLogo decorative size={30} />
              </Link>
              <ThemeToggle compact />
            </div>

            <div className="auth-card__heading">
              <p className="auth-card__eyebrow">SECURE SIGN-IN</p>
              <h2 id="login-title">Welcome back</h2>
              <p>Sign in to continue to your MARKADO workspace.</p>
            </div>

            {flash?.success && <p className="auth-registration-note" role="status">{flash.success}</p>}
            <form id="login-form" className="auth-form" onSubmit={submit} noValidate>
              <div className="auth-field">
                <label htmlFor="email">Email address</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  autoComplete="username"
                  placeholder="you@example.com"
                  required
                  value={form.data.email}
                  aria-invalid={Boolean(form.errors.email)}
                  aria-describedby={form.errors.email ? 'email-error' : undefined}
                  onChange={(event) => form.setData('email', event.target.value)}
                />
                {form.errors.email && <span className="auth-error" id="email-error" role="alert">{form.errors.email}</span>}
              </div>

              <div className="auth-field">
                <label htmlFor="password">Password</label>
                <div className="auth-password-field">
                  <input
                    id="password"
                    name="password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    placeholder="Enter your password"
                    required
                    value={form.data.password}
                    aria-invalid={Boolean(form.errors.password)}
                    aria-describedby={form.errors.password ? 'password-error' : undefined}
                    onChange={(event) => form.setData('password', event.target.value)}
                  />
                  <button
                    type="button"
                    className="auth-password-toggle"
                    onClick={() => setShowPassword((value) => !value)}
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    aria-pressed={showPassword}
                  >
                    {showPassword ? 'Hide' : 'Show'}
                  </button>
                </div>
                {form.errors.password && <span className="auth-error" id="password-error" role="alert">{form.errors.password}</span>}
              </div>

              <button className="button button--primary auth-submit" type="submit" disabled={form.processing}>
                {form.processing ? 'Signing in…' : 'Sign in'}
              </button>
            </form>

            <div className="auth-signup-row">
              <span>{registration?.available ? "Don't have an account?" : 'New to MARKADO?'}</span>
              <Link href="/register">{registration?.available ? 'Create Student Account' : 'Review access requirements'}</Link>
            </div>
            <p className="auth-card__security-foot">Your account and quiz records stay protected by MARKADO.</p>
            <Link className="auth-back-link" href="/">Return to MARKADO home</Link>
          </div>
        </section>
      </main>
    </div>
  )
}
