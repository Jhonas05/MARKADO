import { fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Register from '../server/resources/js/pages/Auth/Register.jsx'

const state = vi.hoisted(() => ({ post: vi.fn(), errors: {}, processing: false }))

vi.mock('@inertiajs/react', async () => {
  const { useState } = await import('react')
  return {
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    useForm(initial) {
      const [data, setData] = useState(initial)
      return { data, errors: state.errors, processing: state.processing,
        setData: (field, value) => setData((current) => ({ ...current, [field]: value })),
        post: (path, options) => state.post(path, data, options), reset: vi.fn() }
    },
  }
})

const registration = { available: true, requirements: ['Use a verified school identity.'], verificationStates: [] }

describe('Student registration form', () => {
  beforeEach(() => { state.post.mockClear(); state.errors = {}; state.processing = false })

  it('submits only the four required fields and preserves official number zeros', () => {
    render(<Register registration={registration} />)
    fireEvent.change(screen.getByLabelText('Official student number'), { target: { value: '00009401' } })
    fireEvent.change(screen.getByLabelText('Institutional email address'), { target: { value: 'new@students.example.edu' } })
    fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'Synthetic-Password-9400' } })
    fireEvent.change(screen.getByLabelText('Confirm password'), { target: { value: 'Synthetic-Password-9400' } })
    fireEvent.click(screen.getByRole('button', { name: 'Create account' }))
    expect(state.post).toHaveBeenCalledWith('/register', {
      student_number: '00009401', email: 'new@students.example.edu',
      password: 'Synthetic-Password-9400', password_confirmation: 'Synthetic-Password-9400',
    }, expect.any(Object))
    expect(screen.queryByLabelText(/full name/i)).not.toBeInTheDocument()
    expect(screen.queryByLabelText(/role/i)).not.toBeInTheDocument()
  })

  it('associates server errors with their fields and blocks duplicate processing', () => {
    state.errors = { email: 'Use your authorized school-issued institutional email address.' }
    state.processing = true
    render(<Register registration={registration} />)
    expect(screen.getByLabelText('Institutional email address')).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByRole('alert')).toHaveTextContent(state.errors.email)
    expect(screen.getByRole('button', { name: 'Creating account…' })).toBeDisabled()
    expect(screen.getByLabelText('Password')).toBeDisabled()
  })

  it('keeps unavailable environments disabled', () => {
    render(<Register registration={{ ...registration, available: false }} />)
    expect(screen.getByRole('button', { name: 'Registration unavailable' })).toBeDisabled()
    expect(screen.getByLabelText('Official student number')).toBeDisabled()
    expect(state.post).not.toHaveBeenCalled()
  })
})
