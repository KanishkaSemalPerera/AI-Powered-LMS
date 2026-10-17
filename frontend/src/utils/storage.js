// Centralised access to browser storage. Wrapped in try/catch because storage
// can be unavailable (private mode, blocked site data).

const TOKEN_KEY = 'lms.token'
export const THEME_KEY = 'lms.theme'

function read(key) {
  try {
    return window.localStorage.getItem(key)
  } catch {
    return null
  }
}

function write(key, value) {
  try {
    if (value === null || value === undefined) {
      window.localStorage.removeItem(key)
    } else {
      window.localStorage.setItem(key, value)
    }
  } catch {
    // Storage unavailable: the app still works, the value just won't persist.
  }
}

export const tokenStorage = {
  get: () => read(TOKEN_KEY),
  set: (token) => write(TOKEN_KEY, token),
  clear: () => write(TOKEN_KEY, null),
}

export const themeStorage = {
  get: () => read(THEME_KEY),
  set: (theme) => write(THEME_KEY, theme),
}
