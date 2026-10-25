import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import MarkadoLogo from './MarkadoLogo.jsx'

describe('MARKADO official logo', () => {
  it('exposes a meaningful accessible name when the wordmark carries branding', () => {
    render(<MarkadoLogo />)

    const logo = screen.getByRole('img', { name: 'MARKADO' })
    expect(logo).toHaveAttribute('width', '1561')
    expect(logo).toHaveAttribute('height', '272')
    expect(logo.closest('.markado-logo')).toHaveClass('markado-logo--wordmark')
  })

  it('uses the official compact symbol and suppresses duplicate announcements', () => {
    const { container } = render(<MarkadoLogo decorative size={30} variant="symbol" />)

    const logo = container.querySelector('img')
    expect(logo).toHaveAttribute('alt', '')
    expect(logo).toHaveAttribute('width', '320')
    expect(logo.closest('.markado-logo')).toHaveClass('markado-logo--symbol')
    expect(logo.closest('.markado-logo')).toHaveStyle({ '--markado-logo-size': '30px' })
  })
})
