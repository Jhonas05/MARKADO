import { Head, Link } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

const icons = {
  disabled: 'lock',
  pending: 'clock',
  'password-recovery': 'mail',
  'password-reset': 'lock',
  'session-expired': 'clock',
}

export default function PolicyGate({ heading, message, state, title }) {
  return (
    <div className="auth-page auth-state-page">
      <Head title={title} />
      <main className="auth-state-card" id="main-content">
        <div className="auth-card__topline">
          <Link className="auth-brand" href="/" aria-label="MARKADO home">
            <BrandMark size={38} />
            <span>MARKADO</span>
          </Link>
          <ThemeToggle compact />
        </div>
        <span className="auth-state-card__icon"><Icon name={icons[state] || 'shield'} size={24} /></span>
        <p className="section-kicker">{title}</p>
        <h1>{heading}</h1>
        <p>{message}</p>
        {state === 'password-recovery' ? (
          <div className="auth-field">
            <label htmlFor="recovery-email">Email address</label>
            <input id="recovery-email" type="email" autoComplete="email" disabled />
          </div>
        ) : null}
        <Link className="button button--primary button--full" href="/login">Return to sign in</Link>
        {state === 'password-recovery' ? <Link className="auth-back-link" href="/reset-password">Password reset requirements</Link> : null}
      </main>
    </div>
  )
}
