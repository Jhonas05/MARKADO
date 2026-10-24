import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import AssessmentTimer from '../../server/resources/js/Components/AssessmentTimer.jsx'
import AttemptNavigation from '../../server/resources/js/Components/AttemptNavigation.jsx'

describe('database-backed assessment components', () => {
  it('renders an urgent display-only countdown accessibly', () => {
    render(<AssessmentTimer label="Question" remaining={59_000} />)

    expect(screen.getByRole('timer')).toHaveClass('assessment-timer--urgent')
    expect(screen.getByText('Question')).toBeInTheDocument()
    expect(screen.getByText('00:59')).toBeInTheDocument()
  })

  it('marks current, acknowledged, and pending progress without answer content', () => {
    render(<AttemptNavigation items={[
      { position: 1, status: 'locked', answered: true, current: false },
      { position: 2, status: 'active', answered: false, current: true },
      { position: 3, status: 'pending', answered: false, current: false },
    ]} />)

    expect(screen.getByRole('list', { name: 'Question progress' })).toBeInTheDocument()
    expect(screen.getByText('Current').closest('li')).toHaveAttribute('aria-current', 'step')
    expect(screen.getByText('Saved').closest('li')).toHaveClass('attempt-navigation__item--answered')
    expect(screen.getByText('pending')).toBeInTheDocument()
  })
})
