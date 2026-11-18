import { fireEvent, render, screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

vi.mock('@inertiajs/react', () => ({
  Head: () => null,
  Link: ({ children, href, ...props }) => <a href={href} {...props}>{children}</a>,
  usePage: () => ({ props: {} }),
  useForm: () => ({ data: { email: '', password: '' }, errors: {}, setData: vi.fn() }),
}))
vi.mock('@prototype/components/ThemeToggle.jsx', () => ({ default: () => null }))

import Login from '../server/resources/js/pages/Auth/Login.jsx'
import Register from '../server/resources/js/pages/Auth/Register.jsx'
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

  it('shows an enabled, streamlined signup form without email verification requirements', () => {
    render(<Register registration={{ available: true, mode: 'unverified', requirements: [], verificationStates: [] }} />)
    expect(screen.getByRole('heading', { name: 'Create your Student account' })).toBeInTheDocument()
    expect(screen.getByLabelText('Student number')).toBeInTheDocument()
    expect(screen.getByLabelText('Email address')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
    expect(screen.getByLabelText('Confirm password')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Create account' })).toBeEnabled()
    expect(screen.queryByRole('heading', { name: 'Required checks' })).not.toBeInTheDocument()
    expect(screen.queryByRole('heading', { name: 'Verification states' })).not.toBeInTheDocument()
  })

  it('renders an accessible MARKADO showcase on sign-in and sign-up', () => {
    const { unmount } = render(<Login registration={{ available: true }} />)
    expect(screen.getByRole('heading', { name: 'Make every quiz count.' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Welcome back' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Make every quiz count.' })).toHaveAttribute('id', 'auth-showcase-title')
    unmount()

    render(<Register registration={{ available: true, mode: 'unverified', requirements: [], verificationStates: [] }} />)
    expect(screen.getByRole('heading', { name: 'Make every quiz count.' })).toBeInTheDocument()
    expect(screen.getByRole('heading', { name: 'Create your Student account' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Create account' })).toBeEnabled()
  })

  it('lets a keyboard-accessible control show and hide the sign-in password', () => {
    render(<Login registration={{ available: true }} />)
    const password = screen.getByLabelText('Password')
    expect(password).toHaveAttribute('type', 'password')
    fireEvent.click(screen.getByRole('button', { name: 'Show password' }))
    expect(password).toHaveAttribute('type', 'text')
    fireEvent.click(screen.getByRole('button', { name: 'Hide password' }))
    expect(password).toHaveAttribute('type', 'password')
  })

  it('explains unavailable recovery without disabled input or a misleading reset action', () => {
    render(<PolicyGate state="password-recovery" title="Password recovery" heading="Recovery is currently unavailable" message="Contact your school." />)
    expect(screen.getByRole('heading', { name: 'Recovery is currently unavailable' })).toBeInTheDocument()
    expect(screen.queryByLabelText('Email address')).not.toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /reset/i })).not.toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Return to sign in' })).toHaveAttribute('href', '/login')
  })
})
