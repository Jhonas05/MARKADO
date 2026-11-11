import { useEffect, useState } from 'react'
import Icon from './Icon.jsx'
import {
  applyTheme,
  persistThemePreference,
  readThemePreference,
} from '../theme.js'

const options = [
  { value: 'light', label: 'Light', icon: 'sun' },
  { value: 'dark', label: 'Dark', icon: 'moon' },
  { value: 'system', label: 'System', icon: 'monitor' },
]

export default function ThemeToggle({ compact = false }) {
  const [preference, setPreference] = useState(() => (
    document.documentElement.dataset.themePreference || readThemePreference()
  ))

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)')
    persistThemePreference(preference)
    applyTheme(preference, { systemDark: media.matches })

    if (preference !== 'system') return undefined

    const handleChange = (event) => applyTheme('system', { systemDark: event.matches })
    media.addEventListener('change', handleChange)
    return () => media.removeEventListener('change', handleChange)
  }, [preference])

  return (
    <div className={`theme-toggle${compact ? ' theme-toggle--compact' : ''}`} aria-label="Appearance" role="group">
      {options.map((option) => (
        <button
          aria-label={`Use ${option.label.toLowerCase()} theme`}
          aria-pressed={preference === option.value}
          className="theme-toggle__option"
          key={option.value}
          onClick={() => setPreference(option.value)}
          title={option.label}
          type="button"
        >
          <Icon name={option.icon} size={16} />
          <span>{option.label}</span>
        </button>
      ))}
    </div>
  )
}
