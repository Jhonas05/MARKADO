import { fireEvent, render, screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import App from './App.jsx'

describe('MARKADO student prototype', () => {
  it('renders a transparent student dashboard with synthetic-data notice', () => {
    render(<App />)

    expect(
      screen.getByRole('heading', { name: /welcome back, mia/i }),
    ).toBeInTheDocument()
    expect(screen.getByText(/interactive ui prototype/i)).toBeInTheDocument()
    expect(
      screen.getByRole('button', { name: /view instructions/i }),
    ).toBeInTheDocument()
  })

  it('rejects an unknown code and accepts the documented prototype code', () => {
    render(<App />)

    fireEvent.click(screen.getByRole('button', { name: /join with a code/i }))
    const codeInput = screen.getByRole('textbox', { name: /quiz code/i })

    fireEvent.change(codeInput, { target: { value: 'WRONG' } })
    fireEvent.click(screen.getByRole('button', { name: /find quiz/i }))
    expect(screen.getByRole('alert')).toHaveTextContent(/try bio-104/i)

    fireEvent.change(codeInput, { target: { value: 'BIO-104' } })
    fireEvent.click(screen.getByRole('button', { name: /find quiz/i }))
    expect(
      screen.getByRole('heading', { name: /before you begin/i }),
    ).toBeInTheDocument()
  })

  it('supports the instructions, answer, review, and local results flow', () => {
    render(<App />)

    fireEvent.click(screen.getByRole('button', { name: /view instructions/i }))
    const startButton = screen.getByRole('button', {
      name: /start practice attempt/i,
    })
    expect(startButton).toBeDisabled()

    fireEvent.click(
      screen.getByRole('checkbox', {
        name: /i read the prototype instructions/i,
      }),
    )
    expect(startButton).toBeEnabled()
    fireEvent.click(startButton)

    expect(
      screen.getByRole('heading', {
        name: /which statement best describes an ecosystem/i,
      }),
    ).toBeInTheDocument()
    fireEvent.click(
      screen.getByRole('radio', {
        name: /living organisms interacting with one another/i,
      }),
    )
    expect(screen.getByRole('status')).toHaveTextContent(/not saved to a server/i)

    fireEvent.click(screen.getByRole('button', { name: /^review answers$/i }))
    expect(
      screen.getByRole('heading', { name: /review your answers/i }),
    ).toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: /submit prototype/i }))
    const dialog = screen.getByRole('dialog', { name: /submit these answers/i })
    fireEvent.click(
      within(dialog).getByRole('button', { name: /confirm submission/i }),
    )

    expect(
      screen.getByRole('heading', { name: /submission received/i }),
    ).toBeInTheDocument()
    expect(screen.getByLabelText('20 percent')).toBeInTheDocument()
  })

  it('provides an accessible help panel', () => {
    render(<App />)

    fireEvent.click(screen.getByRole('button', { name: /open help and guide/i }))
    const help = screen.getByRole('dialog', { name: /what do you need/i })
    expect(help).toHaveTextContent(/joining a quiz/i)
    fireEvent.click(
      within(help).getByRole('button', { name: /close help and guide/i }),
    )
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })
})
