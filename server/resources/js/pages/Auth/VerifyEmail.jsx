import { Head, Link, router, useForm, usePage } from '@inertiajs/react'
import { useState } from 'react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

export default function VerifyEmail() {
  const { flash } = usePage().props
  const form = useForm({ verification_token: '' })
  const [resending, setResending] = useState(false)

  function verify(event) {
    event.preventDefault()
    if (form.processing) return
    form.post('/register/verification', { onFinish: () => form.reset('verification_token') })
  }

  function resend() {
    if (resending) return
    setResending(true)
    router.post('/register/resend', {}, { onFinish: () => setResending(false), preserveScroll: true })
  }

  return (
    <div className="auth-page">
      <Head title="Verify your email" />
      <main className="auth-shell auth-shell--single" id="main-content">
        <section className="auth-card auth-card--registration" aria-labelledby="verification-title">
          <div className="auth-card__topline">
            <Link className="auth-brand" href="/" aria-label="MARKADO home"><MarkadoLogo decorative size={30} /></Link>
            <ThemeToggle compact />
          </div>
          <p className="section-kicker">Student access</p>
          <h1 id="verification-title">Check your school email</h1>
          <p className="auth-card__intro">If your details can be registered, verification instructions will be sent to your institutional mailbox. Existing accounts must sign in.</p>
          {flash?.success && <p className="auth-registration-note" role="status">{flash.success}</p>}
          <form className="auth-form" onSubmit={verify} noValidate>
            <div className="auth-field">
              <label htmlFor="verification-token">Verification code</label>
              <input id="verification-token" name="verification_token" type="password" autoComplete="off" spellCheck={false} required
                value={form.data.verification_token} onChange={(event) => form.setData('verification_token', event.target.value)}
                aria-invalid={Boolean(form.errors.verification_token)} aria-describedby="verification-hint verification-error" />
              <p className="auth-field__hint" id="verification-hint">Paste the full code from your email. It expires after 30 minutes and can be used once.</p>
              <span className="auth-error" id="verification-error" role={form.errors.verification_token ? 'alert' : undefined}>{form.errors.verification_token}</span>
            </div>
            <button className="button button--primary auth-submit" disabled={form.processing}>{form.processing ? 'Verifying…' : 'Verify email and activate'}</button>
          </form>
          <button className="button button--secondary auth-submit" type="button" disabled={resending} onClick={resend}>{resending ? 'Requesting…' : 'Resend verification email'}</button>
          <p className="auth-field__hint">Wait at least 60 seconds before requesting another email. Up to five emails can be issued in a rolling hour. A new code replaces the previous one.</p>
          <p className="auth-card__footer"><Link href="/login">Back to Sign in</Link></p>
        </section>
      </main>
    </div>
  )
}
