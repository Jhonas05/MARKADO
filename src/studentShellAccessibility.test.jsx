import { render, screen, within } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import StudentShell from '../server/resources/js/Layouts/StudentShell.jsx'

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, ...props }) => <a {...props}>{children}</a>,
  usePage: () => ({ props: {} }),
}))

vi.mock('@prototype/components/ThemeToggle.jsx', () => ({ default: () => null }))

describe('Student workspace navigation', () => {
  it('includes the visible workspace label in the home link accessible name', () => {
    render(
      <StudentShell
        homeHref="/student/dashboard"
        navigation={[]}
        profile={{ name: 'Synthetic Student', initials: 'SS', detail: 'Student' }}
        title="Workspace"
      >
        <main id="main-content">Workspace</main>
      </StudentShell>,
    )

    const link = screen.getByRole('link', { name: /assessment workspace, markado student home/i })
    expect(within(link).getByText('Assessment workspace')).toBeInTheDocument()
    expect(link).toHaveAttribute('href', '/student/dashboard')
  })
  it('keeps Join quiz, Account, Help and reward tabs reachable on mobile', () => {
    const navigation = [
      { key: 'workspace', href: '/student/dashboard', icon: 'home', label: 'Workspace' },
      { key: 'join', href: '/student/quizzes/join', icon: 'book', label: 'Join quiz' },
      { key: 'collection', href: '/student/characters/collection', icon: 'trophy', label: 'Collection' },
      { key: 'shop', href: '/student/characters/shop', icon: 'spark', label: 'Shop' },
      { key: 'account', href: '/student/dashboard#account', icon: 'user', label: 'Account' },
      { key: 'help', href: '/help', icon: 'help', label: 'Help' },
    ]

    render(
      <StudentShell
        active="workspace"
        authenticated
        homeHref="/student/dashboard"
        navigation={navigation}
        profile={{ name: 'Synthetic Student', initials: 'SS', detail: 'Student' }}
        title="Workspace"
      >
        <main id="main-content">Workspace</main>
      </StudentShell>,
    )

    const mobileNav = screen.getByRole('navigation', { name: 'Student mobile navigation' })
    for (const item of navigation) {
      expect(within(mobileNav).getByRole('link', { name: item.label })).toHaveAttribute('href', item.href)
    }
    expect(within(mobileNav).getAllByRole('link')).toHaveLength(navigation.length)
  })

})
