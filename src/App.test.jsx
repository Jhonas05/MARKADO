import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import App from './App.jsx'

describe('MARKADO foundation', () => {
  it('renders the student prototype heading', () => {
    render(<App />)

    expect(
      screen.getByRole('heading', { name: /student assessment prototype/i }),
    ).toBeInTheDocument()
  })
})

