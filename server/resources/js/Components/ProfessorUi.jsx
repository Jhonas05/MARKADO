import Icon from '@prototype/components/Icon.jsx'

export function StatusBadge({ children, tone = 'neutral' }) {
  return <span className={`prof-status prof-status--${tone}`}>{children}</span>
}

export function ScreenHeader({ eyebrow, title, description, actions }) {
  return (
    <header className="prof-page-heading">
      <div>
        <p className="section-kicker">{eyebrow}</p>
        <h1>{title}</h1>
        {description ? <p>{description}</p> : null}
      </div>
      {actions ? <div className="prof-page-heading__actions">{actions}</div> : null}
    </header>
  )
}

export function PolicyBanner() {
  return (
    <div className="prof-prototype-banner" role="note">
      <Icon name="shield" size={17} />
      <p>
        <strong>Controlled assessment boundary.</strong> Immutable revisions and
        school-managed eligibility are active. Timer and resume policies are approved,
        while production attempt activation, scoring, result feedback, and privileged
        roster changes remain gated until their implementation and security checks pass.
      </p>
    </div>
  )
}

export function FieldError({ message }) {
  return message ? <small className="prof-field-error">{message}</small> : null
}
