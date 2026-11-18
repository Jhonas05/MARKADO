import { Link } from '@inertiajs/react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'

const highlights = [
  {
    label: '01',
    title: 'Timed quizzes',
    description: 'Keep the session and each question on track.',
  },
  {
    label: '02',
    title: 'Join with a code',
    description: 'Get to an assigned quiz without searching through links.',
  },
  {
    label: '03',
    title: 'Organized results',
    description: 'View released scores and export reports when authorized.',
  },
]

export default function AuthShowcase() {
  return (
    <section className="auth-showcase" aria-labelledby="auth-showcase-title">
      <div className="auth-showcase__top">
        <Link href="/" className="auth-showcase__brand" aria-label="MARKADO home">
          <span className="auth-showcase__brand-mark"><MarkadoLogo decorative size={28} /></span>
          <span>Quiz &amp; assessment platform</span>
        </Link>
      </div>

      <div className="auth-showcase__content">
        <p className="auth-showcase__eyebrow">BUILT FOR FOCUSED LEARNING</p>
        <h1 id="auth-showcase-title">Make every quiz <em>count.</em></h1>
        <p className="auth-showcase__lead">
          Create, take, and manage quizzes in one organized workspace.
          Clear timers, simple navigation, and results you can trust.
        </p>

        <ul className="auth-showcase__features">
          {highlights.map((item) => (
            <li className="auth-showcase__feature" key={item.label}>
              <span className="auth-showcase__feature-number" aria-hidden="true">{item.label}</span>
              <div>
                <strong>{item.title}</strong>
                <span>{item.description}</span>
              </div>
            </li>
          ))}
        </ul>
      </div>

      <div className="auth-showcase__bottom">
        <div className="auth-showcase__timer-art" aria-hidden="true"><span /></div>
        <p>Designed for students and instructors.<br />Simple to use. Ready when you are.</p>
      </div>
    </section>
  )
}
