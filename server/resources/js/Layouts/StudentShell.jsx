import { Head, Link } from '@inertiajs/react'
import BrandMark from '@prototype/components/BrandMark.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

function NavigationItem({ active, item, mobile = false }) {
  const className = mobile
    ? `student-bottom-nav__item${active ? ' student-bottom-nav__item--active' : ''}`
    : `student-sidebar__link${active ? ' student-sidebar__link--active' : ''}`

  if (item.disabled) {
    return (
      <span className={`${className} student-navigation--disabled`} aria-disabled="true">
        <Icon name={item.icon} size={mobile ? 19 : 18} />
        <span>{item.label}</span>
      </span>
    )
  }

  return (
    <Link className={className} href={item.href} aria-current={active ? 'page' : undefined}>
      <Icon name={item.icon} size={mobile ? 19 : 18} />
      <span>{item.label}</span>
    </Link>
  )
}

export default function StudentShell({
  active,
  authenticated = false,
  children,
  homeHref = '/',
  navigation,
  profile,
  title,
}) {
  return (
    <div className="student-shell">
      <Head title={title} />
      <a className="skip-link" href="#main-content">Skip to content</a>

      <aside className="student-sidebar" aria-label="Student navigation">
        <Link className="student-sidebar__brand" href={homeHref} aria-label="MARKADO student home">
          <BrandMark size={34} />
          <span>
            <strong>MARKADO</strong>
            <small>Assessment workspace</small>
          </span>
        </Link>

        <nav className="student-sidebar__nav" aria-label="Student navigation">
          <p>Student</p>
          {navigation.map((item) => (
            <NavigationItem active={item.key === active} item={item} key={item.key} />
          ))}
        </nav>

        <div className="student-sidebar__footer">
          <div>
            <p>Appearance</p>
            <ThemeToggle />
          </div>
          {authenticated ? (
            <Link className="student-sidebar__utility" href="/logout" method="delete" as="button">
              <Icon name="lock" size={17} />
              <span>Sign out</span>
            </Link>
          ) : null}
          <div className="student-sidebar__profile" aria-label={`Signed in as ${profile.name}`}>
            <span>{profile.initials}</span>
            <div>
              <strong>{profile.name}</strong>
              <small>{profile.detail}</small>
            </div>
          </div>
        </div>
      </aside>

      <div className="student-shell__content">
        <header className="student-mobile-header">
          <Link className="student-mobile-header__brand" href={homeHref} aria-label="MARKADO student home">
            <BrandMark size={30} />
            <strong>MARKADO</strong>
          </Link>
          <div className="student-mobile-header__actions">
            <ThemeToggle compact />
            <span className="student-mobile-header__avatar" aria-label={`Signed in as ${profile.name}`}>
              {profile.initials}
            </span>
          </div>
        </header>

        {children}
      </div>

      <nav className="student-bottom-nav" aria-label="Student mobile navigation">
        {navigation.slice(0, 4).map((item) => (
          <NavigationItem active={item.key === active} item={item} key={item.key} mobile />
        ))}
      </nav>
    </div>
  )
}
