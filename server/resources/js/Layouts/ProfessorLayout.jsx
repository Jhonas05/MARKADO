import { Head, Link, usePage } from '@inertiajs/react'
import { useState } from 'react'
import MarkadoLogo from '@prototype/components/MarkadoLogo.jsx'
import Icon from '@prototype/components/Icon.jsx'
import ThemeToggle from '@prototype/components/ThemeToggle.jsx'

const navigation = [
  {
    label: 'Quizzes',
    items: [
      { href: '/professor/quizzes', label: 'My quizzes', icon: 'book' },
      { href: '/professor/quizzes/create', label: 'Create quiz', icon: 'plus' },
    ],
  },
]

export default function ProfessorLayout({ children, title }) {
  const { auth, flash } = usePage().props
  const [mobileOpen, setMobileOpen] = useState(false)
  const path = window.location.pathname
  const initials = auth.user.name
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return (
    <div className="prof-app">
      <Head title={title} />
      <a className="skip-link" href="#main-content">Skip to main content</a>

      <header className="prof-header">
        <div className="prof-header__brand">
          <button
            className="prof-mobile-menu"
            type="button"
            onClick={() => setMobileOpen(true)}
            aria-label="Open professor navigation"
            aria-controls="professor-navigation"
            aria-expanded={mobileOpen}
          >
            <Icon name="menu" />
          </button>
          <span className="prof-header__brand-lockup">
            <MarkadoLogo decorative size={20} />
            <small>Professor workspace</small>
          </span>
        </div>
        <div className="prof-header__context">
          <small>Quiz management</small>
          <strong>{title}</strong>
        </div>
        <div className="prof-header__actions">
          <Link className="prof-view-switch" href="/logout" method="delete" as="button" aria-label="Sign out of Professor workspace">
            <Icon name="lock" size={17} />
            <span>Sign out</span>
          </Link>
          <div className="prof-profile" aria-label={`Signed in as ${auth.user.name}`}>
            <span>{initials}</span>
            <div>
              <strong>{auth.user.name}</strong>
              <small>{auth.user.roleLabel}</small>
            </div>
          </div>
        </div>
      </header>

      <div className="prof-frame">
        {mobileOpen ? (
          <button
            className="prof-nav-backdrop"
            type="button"
            aria-label="Close professor navigation"
            onClick={() => setMobileOpen(false)}
          />
        ) : null}
        <aside
          className={`prof-sidebar${mobileOpen ? ' prof-sidebar--open' : ''}`}
          id="professor-navigation"
          aria-label="Professor navigation"
        >
          <Link className="prof-sidebar__brand" href="/professor/quizzes" onClick={() => setMobileOpen(false)}>
            <span className="prof-sidebar__brand-lockup">
              <MarkadoLogo decorative size={22} />
              <small>Professor workspace</small>
            </span>
          </Link>
          <div className="prof-sidebar__intro">
            <span>Professor</span>
            <strong>Assessment workspace</strong>
            <small>Create · publish · review</small>
          </div>
          <nav aria-label="Professor navigation">
            {navigation.map((group) => (
              <div className="prof-nav-group" key={group.label}>
                <p>{group.label}</p>
                {group.items.map((item) => {
                  const active = item.href === '/professor/quizzes/create'
                    ? path === item.href
                    : item.href && (
                      path === item.href
                      || (path.startsWith(`${item.href}/`) && path !== '/professor/quizzes/create')
                    )

                  if (item.disabled) {
                    return (
                      <span className="prof-nav-link prof-nav-link--disabled" aria-disabled="true" key={item.label}>
                        <Icon name={item.icon} size={17} />
                        <span>{item.label}</span>
                      </span>
                    )
                  }

                  return (
                    <Link
                      className={active ? 'prof-nav-link prof-nav-link--active' : 'prof-nav-link'}
                      href={item.href}
                      key={item.label}
                      onClick={() => setMobileOpen(false)}
                      aria-current={active ? 'page' : undefined}
                    >
                      <Icon name={item.icon} size={17} />
                      <span>{item.label}</span>
                    </Link>
                  )
                })}
              </div>
            ))}
          </nav>
          <div className="prof-sidebar__appearance">
            <p>Appearance</p>
            <ThemeToggle />
          </div>
          <div className="prof-sidebar__account">
            <div className="prof-sidebar__profile" aria-label={`Signed in as ${auth.user.name}`}><span>{initials}</span><div><strong>{auth.user.name}</strong><small>{auth.user.roleLabel}</small></div></div>
            <Link className="prof-sidebar__signout" href="/logout" method="delete" as="button" aria-label="Sign out of Professor workspace"><Icon name="lock" size={17} /><span>Sign out</span></Link>
          </div>
          <div className="prof-sidebar__policy">
            <Icon name="shield" size={18} />
            <div>
              <strong>Protected publishing</strong>
              <span>Only verified, assigned students can access a published quiz.</span>
            </div>
          </div>
        </aside>

        <main className="prof-main" id="main-content">
          {flash?.success ? (
            <div className="prof-prototype-banner prof-success-banner" role="status">
              <Icon name="checkCircle" size={17} />
              <p>{flash.success}</p>
            </div>
          ) : null}
          {children}
          <footer className="prof-footer">
            <span>MARKADO Professor workspace</span>
            <span>Asia/Manila · School-managed access</span>
          </footer>
        </main>
      </div>
    </div>
  )
}
