import { useCallback, useEffect, useMemo, useState } from 'react'
import { themeStorage } from '@/utils/storage'
import { ThemeContext } from './ThemeContext'

const media = () => window.matchMedia('(prefers-color-scheme: dark)')

/**
 * Manages light/dark/system theme. The choice is saved in localStorage and
 * applied as a "dark" class on <html> (index.html applies it before first paint).
 */
export function ThemeProvider({ children }) {
  const [theme, setThemeState] = useState(() => themeStorage.get() || 'system')
  const [systemDark, setSystemDark] = useState(() => media().matches)

  // Follow OS theme changes while in "system" mode.
  useEffect(() => {
    const query = media()
    const onChange = (event) => setSystemDark(event.matches)
    query.addEventListener('change', onChange)
    return () => query.removeEventListener('change', onChange)
  }, [])

  const resolvedTheme = theme === 'system' ? (systemDark ? 'dark' : 'light') : theme

  useEffect(() => {
    document.documentElement.classList.toggle('dark', resolvedTheme === 'dark')
  }, [resolvedTheme])

  const setTheme = useCallback((next) => {
    themeStorage.set(next)
    setThemeState(next)
  }, [])

  const toggleTheme = useCallback(() => {
    setTheme(resolvedTheme === 'dark' ? 'light' : 'dark')
  }, [resolvedTheme, setTheme])

  const value = useMemo(
    () => ({ theme, resolvedTheme, setTheme, toggleTheme }),
    [theme, resolvedTheme, setTheme, toggleTheme],
  )

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}
