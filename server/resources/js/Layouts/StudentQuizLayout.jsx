import { Head, Link, usePage } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'
import StudentQuizNavigation from '../Components/StudentQuizNavigation.jsx'

export default function StudentQuizLayout({ active = 'join', children, title }) {
  const { auth } = usePage().props
  const user = auth.user
  const initials = user.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()

  return (
    <div className="app foundation-app student-preflight-app">
      <Head title={title} />
      <a className="skip-link" href="#main-content">Skip to content</a>

      <header className="app-header">
        <div className="app-header__inner student-preflight-header">
          <Link className="brand" href="/workspace" aria-label="MARKADO authenticated workspace">
            <BrandMark />
            <span className="brand__name">MARKADO</span>
          </Link>

          <StudentQuizNavigation active={active} />

          <div className="header-actions">
            <div className="student-chip" aria-label={`Signed in as ${user.name}`}>
              <span className="student-chip__avatar">{initials}</span>
              <span className="student-chip__copy">
                <strong>{user.name}</strong>
                <small>Student account</small>
              </span>
            </div>
          </div>
        </div>
      </header>

      {children}
    </div>
  )
}
