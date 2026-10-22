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
    expect(screen.getByRole('alert')).toHaveTextContent(
      /check the code with your professor/i,
    )

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

    expect(screen.queryByRole('banner')).not.toBeInTheDocument()
    expect(
      screen.queryByRole('navigation', { name: /mobile navigation/i }),
    ).not.toBeInTheDocument()
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

  it('provides the four-item student mobile navigation structure', () => {
    render(<App />)

    const mobileNavigation = screen.getByRole('navigation', {
      name: /mobile navigation/i,
    })
    expect(within(mobileNavigation).getAllByRole('button')).toHaveLength(4)
    expect(within(mobileNavigation).getByText('Overview')).toBeInTheDocument()
    expect(within(mobileNavigation).getByText('Quizzes')).toBeInTheDocument()
    expect(within(mobileNavigation).getByText('Results')).toBeInTheDocument()
    expect(within(mobileNavigation).getByText('Help')).toBeInTheDocument()
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

function openAdminPrototype() {
  fireEvent.click(
    screen.getByRole('button', { name: /administrator prototype/i }),
  )
  return screen.getByRole('navigation', { name: /administrator navigation/i })
}

describe('MARKADO administrator prototype', () => {
  it('exposes every Phase 3 workspace and preserves cross-experience navigation', () => {
    render(<App />)

    const navigation = openAdminPrototype()
    expect(
      screen.getByRole('heading', { name: /good afternoon, admin mendoza/i }),
    ).toBeInTheDocument()
    expect(screen.getByText(/administrator ui prototype/i)).toBeInTheDocument()

    const destinations = [
      ['User management', /user management/i],
      ['Assessment oversight', /assessment oversight/i],
      ['Reporting overview', /reporting overview/i],
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

    fireEvent.click(screen.getByRole('button', { name: /professor view/i }))
    expect(
      screen.getByRole('heading', { name: /good afternoon, prof\. reyes/i }),
    ).toBeInTheDocument()
    fireEvent.click(
      screen.getByRole('button', { name: /administrator view/i }),
    )
    fireEvent.click(screen.getByRole('button', { name: /student view/i }))
    expect(
      screen.getByRole('heading', { name: /welcome back, mia/i }),
    ).toBeInTheDocument()
  })

  it('filters the synthetic account directory and presents an empty state', () => {
    render(<App />)
    const navigation = openAdminPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /user management/i }),
    )

    fireEvent.change(screen.getByRole('combobox', { name: /role/i }), {
      target: { value: 'Instructor' },
    })
    expect(screen.getAllByTestId('admin-user-row')).toHaveLength(3)
    expect(screen.queryByText('Mia Santos')).not.toBeInTheDocument()

    fireEvent.change(screen.getByRole('textbox', { name: /search accounts/i }), {
      target: { value: 'Elena' },
    })
    expect(screen.getAllByTestId('admin-user-row')).toHaveLength(1)
    expect(screen.getByText('Prof. Elena Reyes')).toBeInTheDocument()

    fireEvent.change(screen.getByRole('textbox', { name: /search accounts/i }), {
      target: { value: 'no-such-synthetic-account' },
    })
    expect(screen.getByRole('status')).toHaveTextContent(/no accounts match/i)
    fireEvent.click(screen.getByRole('button', { name: /clear filters/i }))
    expect(screen.getAllByTestId('admin-user-row')).toHaveLength(8)
  })

  it('keeps account-management actions provisional and simulated', () => {
    render(<App />)
    const navigation = openAdminPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /user management/i }),
    )
    fireEvent.click(
      screen.getByRole('button', { name: /inspect mia santos/i }),
    )

    const accountDialog = screen.getByRole('dialog', { name: /mia santos/i })
    expect(accountDialog).toHaveTextContent(/no grades, answers, passwords/i)
    fireEvent.click(
      within(accountDialog).getByRole('button', {
        name: /preview support action/i,
      }),
    )

    const confirmation = screen.getByRole('dialog', {
      name: /preview this support action/i,
    })
    expect(confirmation).toHaveTextContent(/will not approve, disable, edit/i)
    fireEvent.click(
      within(confirmation).getByRole('button', {
        name: /confirm mock preview/i,
      }),
    )
    expect(screen.getByRole('status')).toHaveTextContent(/no account was changed/i)
  })

  it('filters assessments and opens a privacy-conscious detail preview', () => {
    render(<App />)
    const navigation = openAdminPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /assessment oversight/i }),
    )
    fireEvent.change(screen.getByRole('combobox', { name: /status/i }), {
      target: { value: 'Published' },
    })
    expect(screen.getByText('Ecosystems checkpoint')).toBeInTheDocument()
    expect(screen.queryByText('Cell division review')).not.toBeInTheDocument()
    fireEvent.click(screen.getByRole('button', { name: /view details/i }))

    const detail = screen.getByRole('dialog', {
      name: /ecosystems checkpoint/i,
    })
    expect(detail).toHaveTextContent(/omits learner grades, responses/i)
    expect(detail).toHaveTextContent(/27 of 32 submitted/i)
  })

  it('validates reporting dates and confirms mock-only aggregate exports', () => {
    render(<App />)
    const navigation = openAdminPrototype()
    fireEvent.click(
      within(navigation).getByRole('button', { name: /reporting overview/i }),
    )

    fireEvent.change(screen.getByLabelText('From'), {
      target: { value: '2026-10-25' },
    })
    expect(screen.getByRole('alert')).toHaveTextContent(/end date must be/i)
    expect(screen.getByRole('button', { name: /apply range/i })).toBeDisabled()

    fireEvent.change(screen.getByLabelText('From'), {
      target: { value: '2026-10-01' },
    })
    fireEvent.click(screen.getByRole('button', { name: /preview mock export/i }))
    const exportDialog = screen.getByRole('dialog', {
      name: /prepare an aggregate report preview/i,
    })
    expect(exportDialog).toHaveTextContent(/no individual grades/i)
    fireEvent.click(
      within(exportDialog).getByRole('button', {
        name: /prepare mock summary/i,
      }),
    )
    expect(screen.getByRole('status')).toHaveTextContent(/no file was generated/i)
  })
})
