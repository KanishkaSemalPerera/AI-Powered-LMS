import { createContext } from 'react'

/** { theme: 'light' | 'dark' | 'system', resolvedTheme: 'light' | 'dark', setTheme, toggleTheme } */
export const ThemeContext = createContext(null)
