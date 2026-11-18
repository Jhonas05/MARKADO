import { render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, href, ...props }) => <a href={href} {...props}>{children}</a>,
  usePage: () => ({ props: {} }),
  useForm: () => ({ data: { email: '', password: '' }, errors: {}, setData: vi.fn() }),
}))
vi.mock('@prototype/components/ThemeToggle.jsx', () => ({ default: () => null }))

import Login from '../server/resources/js/pages/Auth/Login.jsx'
import PolicyGate from '../server/resources/js/pages/Auth/PolicyGate.jsx'

describe('student signup and recovery surfaces', () => {
  it('offers account creation when signup is available', () => {
    render(<Login registration={{ available: true }} />)
    expect(screen.getByRole('link', { name: 'Create Student Account' })).toHaveAttribute('href', '/register')
  })

  it('does not advertise account creation when signup is unavailable', () => {
    render(<Login registration={{ available: false }} />)
    expect(screen.queryByRole('link', { name: 'Create Student Account' })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Review access requirements' })).toHaveAttribute('href', '/register')
  })

  it('explains unavailable recovery without disabled input or a misleading reset action', () => {
    render(<PolicyGate state="password-recovery" title="Password recovery" heading="Recovery is currently unavailable" message="Contact your school." />)
    expect(screen.getByRole('heading', { name: 'Recovery is currently unavailable' })).toBeInTheDocument()
    expect(screen.queryByLabelText('Email address')).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /reset/i })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Return to sign in' })).toHaveAttribute('href', '/login')
  })
})
