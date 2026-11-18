import { render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, href, ...props }) => <a href={href} {...props}>{children}</a>,
  usePage: () => ({ props: { auth: { user: null } } }),
  useForm: () => ({ data: { email: '', password: '' }, errors: {}, setData: vi.fn() }),
}))
vi.mock('@prototype/components/ThemeToggle.jsx', () => ({ default: () => null }))

import Login from '../server/resources/js/pages/Auth/Login.jsx'
import Register from '../server/resources/js/pages/Auth/Register.jsx'
import PolicyGate from '../server/resources/js/pages/Auth/PolicyGate.jsx'
import Welcome from '../server/resources/js/pages/Welcome.jsx'

describe('student signup and recovery surfaces', () => {
  it('offers the landing-page signup link only when registration is available', () => {
    const { rerender } = render(<Welcome accountPolicies={{ publicStudentRegistration: true, studentClaimSignup: true }} />)
    expect(screen.getByRole('link', { name: 'Create Student Account' })).toHaveAttribute('href', '/register')

    rerender(<Welcome accountPolicies={{ publicStudentRegistration: false }} />)
    expect(screen.queryByRole('link', { name: 'Create Student Account' })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Student access requirements' })).toHaveAttribute('href', '/register')
  })

  it('offers account creation when signup is available', () => {
    render(<Login registration={{ available: true }} />)
    expect(screen.getByRole('link', { name: 'Create Student Account' })).toHaveAttribute('href', '/register')
  })

  it('does not advertise account creation when signup is unavailable', () => {
    render(<Login registration={{ available: false }} />)
    expect(screen.queryByRole('link', { name: 'Create Student Account' })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Review access requirements' })).toHaveAttribute('href', '/register')
  })

  it('offers self-service signup and explains the separate email and academic checks', () => {
    render(<Register registration={{ available: true, claimSignup: true }} />)
    expect(screen.getByRole('heading', { name: 'Create your Student account' })).toBeInTheDocument()
    expect(screen.getByLabelText('Official student number')).toBeInTheDocument()
    expect(screen.getByLabelText('Institutional email address')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
    expect(screen.getByLabelText('Confirm password')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Create account' })).toBeEnabled()
    expect(screen.getByText('Verify your email to activate your Student account.')).toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Verification states' })).not.toBeInTheDocument()
  })

  it('explains unavailable recovery without disabled input or a misleading reset action', () => {
    render(<PolicyGate state="password-recovery" title="Password recovery" heading="Recovery is currently unavailable" message="Contact your school." />)
    expect(screen.getByRole('heading', { name: 'Recovery is currently unavailable' })).toBeInTheDocument()
    expect(screen.queryByLabelText('Email address')).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /reset/i })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Return to sign in' })).toHaveAttribute('href', '/login')
  })
})
