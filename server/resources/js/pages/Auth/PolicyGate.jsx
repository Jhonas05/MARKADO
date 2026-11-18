import { Head, Link } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
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
            <MarkadoLogo decorative size={30} />
          </Link>
          <ThemeToggle compact />
        </div>
        <span className="auth-state-card__icon"><Icon name={icons[state] || 'shield'} size={24} /></span>
        <p className="section-kicker">{title}</p>
        <h1>{heading}</h1>
        <p>{message}</p>
        <Link className="button button--primary button--full" href="/login">Return to sign in</Link>
      </main>
    </div>
  )
}
