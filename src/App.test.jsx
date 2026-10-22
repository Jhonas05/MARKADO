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

function openProfessorPrototype() {
  fireEvent.click(
    screen.getByRole('button', { name: /professor prototype/i }),
  )
  return screen.getByRole('navigation', { name: /professor navigation/i })
}

describe('MARKADO professor prototype', () => {
  it('preserves the student entry point and exposes every Phase 2 workspace', () => {
    render(<App />)

    const navigation = openProfessorPrototype()
    expect(
      screen.getByRole('heading', { name: /good afternoon, prof\. reyes/i }),
    ).toBeInTheDocument()
    expect(screen.getByText(/all learners, activity, scores/i)).toBeInTheDocument()

    const destinations = [
      ['My quizzes', /my quizzes/i],
      ['Create quiz', /create a new quiz/i],
      ['Manual editor', /manual question editor/i],
      ['AI generation', /ai question generation/i],
      ['Review AI drafts', /review ai drafts/i],
      ['Timer & schedule', /timer and schedule/i],
      ['Randomization', /question randomization/i],
      ['Quiz preview', /quiz preview/i],
      ['Publish & share', /publish and share/i],
      ['Monitoring', /monitor submissions/i],
      ['Results', /^results$/i],
      ['PDF & Word exports', /pdf and word exports/i],
      ['Help & guide', /how can we help/i],
    ]

    destinations.forEach(([linkName, heading]) => {
      fireEvent.click(
        within(navigation).getByRole('button', { name: linkName }),
      )
      expect(
        screen.getByRole('heading', { level: 1, name: heading }),
      ).toBeInTheDocument()
    })

    fireEvent.click(screen.getByRole('button', { name: /student view/i }))
    expect(
      screen.getByRole('heading', { name: /welcome back, mia/i }),
    ).toBeInTheDocument()
  }, 15000)

  it('requires source selection and human review for simulated AI drafts', () => {
    render(<App />)
    const navigation = openProfessorPrototype()

    fireEvent.click(
      within(navigation).getByRole('button', { name: /ai generation/i }),
    )
    fireEvent.click(
      screen.getByRole('button', { name: /simulate 3-question generation/i }),
    )
    expect(screen.getByRole('status')).toHaveTextContent(/choose the synthetic/i)

    fireEvent.click(
      screen.getByRole('button', { name: /use synthetic sample material/i }),
    )
    fireEvent.click(
      screen.getByRole('button', { name: /simulate 3-question generation/i }),
    )
    expect(
      screen.getByRole('heading', { name: /review ai drafts/i }),
    ).toBeInTheDocument()

    const draftList = screen.getByRole('complementary', {
      name: /ai-assisted draft questions/i,
    })
    fireEvent.click(
      within(draftList).getByRole('button', {
        name: /which process returns carbon dioxide/i,
      }),
    )
    fireEvent.click(screen.getByRole('button', { name: /approve draft/i }))
    expect(screen.getByRole('status')).toHaveTextContent(/draft approved/i)
  })

  it('keeps publishing gated and presents a mock-only sharing receipt', () => {
    render(<App />)
    const navigation = openProfessorPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /publish & share/i }),
    )

    const publishButton = screen.getByRole('button', {
      name: /simulate publish/i,
    })
    expect(publishButton).toBeDisabled()
    fireEvent.click(
      screen.getByRole('checkbox', {
        name: /understand that publishing is simulated/i,
      }),
    )
    fireEvent.click(publishButton)

    expect(
      screen.getByRole('heading', { name: /your mock quiz is ready to share/i }),
    ).toBeInTheDocument()
    expect(screen.getByText('markado.test/join/BIO-104')).toBeInTheDocument()
  })

  it('sorts synthetic results alphabetically and by ranked score', () => {
    render(<App />)
    const navigation = openProfessorPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /^results$/i }),
    )

    let resultRows = screen.getAllByTestId('result-row')
    expect(within(resultRows[0]).getByText(/aquino, gabriel/i)).toBeInTheDocument()

    fireEvent.click(screen.getByRole('button', { name: /ranked/i }))
    resultRows = screen.getAllByTestId('result-row')
    expect(within(resultRows[0]).getByText(/cruz, carla/i)).toBeInTheDocument()
  })
})
