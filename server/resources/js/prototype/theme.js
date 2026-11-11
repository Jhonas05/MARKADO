export const THEME_STORAGE_KEY = 'markado-theme'
export const THEME_OPTIONS = ['light', 'dark', 'system']

export function normalizeThemePreference(value) {
  return THEME_OPTIONS.includes(value) ? value : 'system'
}

export function readThemePreference(storage = globalThis.localStorage) {
  try {
    return normalizeThemePreference(storage?.getItem(THEME_STORAGE_KEY))
  } catch {
    return 'system'
  }
}

export function resolveTheme(preference, systemDark = false) {
  const normalized = normalizeThemePreference(preference)
  return normalized === 'system' ? (systemDark ? 'dark' : 'light') : normalized
}

export function applyTheme(
  preference,
  {
    root = globalThis.document?.documentElement,
    themeMeta = globalThis.document?.querySelector('meta[name="theme-color"]'),
    systemDark = globalThis.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false,
  } = {},
) {
  const normalized = normalizeThemePreference(preference)
  const resolved = resolveTheme(normalized, systemDark)

  if (root) {
    root.classList.toggle('light', resolved === 'light')
    root.classList.toggle('dark', resolved === 'dark')
    root.dataset.theme = resolved
    root.dataset.themePreference = normalized
  }

  themeMeta?.setAttribute('content', resolved === 'dark' ? '#0A0A0A' : '#FFFFFF')

  return resolved
}

export function persistThemePreference(preference, storage = globalThis.localStorage) {
  const normalized = normalizeThemePreference(preference)

  try {
    storage?.setItem(THEME_STORAGE_KEY, normalized)
  } catch {
    // Storage may be unavailable in hardened/private browsing contexts.
  }

  return normalized
}
