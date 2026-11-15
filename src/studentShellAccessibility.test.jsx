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
})
