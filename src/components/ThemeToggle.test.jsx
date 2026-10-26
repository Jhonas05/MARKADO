import { fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, describe, expect, it } from 'vitest'
import ThemeToggle from './ThemeToggle.jsx'
import { applyTheme, resolveTheme, THEME_STORAGE_KEY } from '../theme.js'

describe('ThemeToggle', () => {
  beforeEach(() => {
    window.localStorage.clear()
    document.documentElement.classList.remove('dark', 'light')
    delete document.documentElement.dataset.theme
    delete document.documentElement.dataset.themePreference
  })

  it('persists a dark preference and applies it to the document', () => {
    render(<ThemeToggle />)

    fireEvent.click(screen.getByRole('button', { name: /use dark theme/i }))

    expect(document.documentElement).toHaveClass('dark')
    expect(document.documentElement).toHaveAttribute('data-theme', 'dark')
    expect(document.documentElement).toHaveAttribute('data-theme-preference', 'dark')
    expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark')
  })

  it('switches back to the light token set', () => {
    render(<ThemeToggle />)
    fireEvent.click(screen.getByRole('button', { name: /use dark theme/i }))
    fireEvent.click(screen.getByRole('button', { name: /use light theme/i }))

    expect(document.documentElement).not.toHaveClass('dark')
    expect(document.documentElement).toHaveClass('light')
    expect(document.documentElement).toHaveAttribute('data-theme', 'light')
    expect(window.localStorage.getItem(THEME_STORAGE_KEY)).toBe('light')
  })

  it('resolves the system preference without inventing another mode', () => {
    expect(resolveTheme('system', true)).toBe('dark')
    expect(resolveTheme('system', false)).toBe('light')
    expect(resolveTheme('unexpected', true)).toBe('dark')
  })

  it('keeps an explicit light preference authoritative on a dark system', () => {
    applyTheme('light', { root: document.documentElement, systemDark: true })

    expect(document.documentElement).toHaveClass('light')
    expect(document.documentElement).not.toHaveClass('dark')
    expect(document.documentElement).toHaveAttribute('data-theme-preference', 'light')
  })

  it('follows a live system preference change while system mode is selected', () => {
    let listener
    window.matchMedia = () => ({
      matches: false,
      addEventListener: (_event, callback) => { listener = callback },
      removeEventListener: () => {},
    })
    render(<ThemeToggle />)
    fireEvent.click(screen.getByRole('button', { name: /use system theme/i }))
    listener({ matches: true })

    expect(document.documentElement).toHaveClass('dark')
    expect(document.documentElement).not.toHaveClass('light')
  })
})
