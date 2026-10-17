import { createContext } from 'react'

/** React Query cache key holding the signed-in user. */
export const AUTH_QUERY_KEY = ['auth', 'me']

/**
 * {
 *   user, status: 'loading' | 'authenticated' | 'guest' | 'error',
 *   isAuthenticated, login, register, logout, setUser, retry
 * }
 */
export const AuthContext = createContext(null)
