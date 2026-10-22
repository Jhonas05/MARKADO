import { Head, Link } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'
import Icon from '@prototype/components/Icon.jsx'

export default function MarkadoLayout({ children, student, title }) {
  return (
    <div className="app foundation-app">
      <Head title={title} />
      <a className="skip-link" href="#main-content">
        Skip to content
      </a>

      <header className="app-header">
        <div className="app-header__inner">
          <Link className="brand" href="/" aria-label="MARKADO student dashboard">
            <BrandMark />
            <span className="brand__name">MARKADO</span>
          </Link>

          <nav className="primary-nav" aria-label="Primary navigation">
            <Link className="nav-link nav-link--active" href="/" aria-current="page">
              <Icon name="home" size={18} />
              <span>Overview</span>
            </Link>
            <span className="nav-link foundation-nav-placeholder" aria-disabled="true">
              <Icon name="book" size={18} />
              <span>My quizzes</span>
            </span>
            <span className="nav-link foundation-nav-placeholder" aria-disabled="true">
              <Icon name="medal" size={18} />
              <span>Results</span>
            </span>
          </nav>

          <div className="header-actions">
            <a className="icon-button" href="#foundation-guide" aria-label="Open foundation guide">
              <Icon name="help" />
            </a>
            <div className="student-chip" aria-label={`Synthetic profile for ${student.name}`}>
              <span className="student-chip__avatar">{student.initials}</span>
              <span className="student-chip__copy">
                <strong>{student.name}</strong>
                <small>{student.studentNumber}</small>
              </span>
            </div>
          </div>
        </div>
      </header>

      {children}
    </div>
  )
}
