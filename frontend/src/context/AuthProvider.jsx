import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, useState } from 'react'
import { toast } from 'sonner'
import * as authApi from '@/api/auth'
import { UNAUTHORIZED_EVENT } from '@/api/client'
import { tokenStorage } from '@/utils/storage'
import { AUTH_QUERY_KEY, AuthContext } from './AuthContext'

/**
 * Holds the signed-in user. The Bearer token lives in localStorage; on page
 * refresh the user is restored by calling GET /auth/me with that token.
 */
export function AuthProvider({ children }) {
  const queryClient = useQueryClient()
  const [token, setToken] = useState(() => tokenStorage.get())
  // True after the user deliberately logs out, so route guards send them home
  // instead of to the login page.
  const [signedOut, setSignedOut] = useState(false)

  const meQuery = useQuery({
    queryKey: AUTH_QUERY_KEY,
    queryFn: authApi.getMe,
    enabled: Boolean(token),
    staleTime: Infinity,
    gcTime: Infinity,
    // Retry network failures only; a 401/403 means the token is no good.
    retry: (count, error) => !error?.response && count < 2,
  })

  const clearSession = useCallback(() => {
    tokenStorage.clear()
    setToken(null)
    queryClient.clear()
  }, [queryClient])

  // The API client fires this when the server rejects our token.
  useEffect(() => {
    const onUnauthorized = (event) => {
      clearSession()
      if (event.detail?.reason === 'deactivated') {
        toast.error('Your account has been deactivated. Please contact support.')
      } else {
        toast.error('Your session has expired. Please log in again.')
      }
    }
    window.addEventListener(UNAUTHORIZED_EVENT, onUnauthorized)
    return () => window.removeEventListener(UNAUTHORIZED_EVENT, onUnauthorized)
  }, [clearSession])

  const startSession = useCallback(
    ({ user, token: newToken }) => {
      tokenStorage.set(newToken)
      queryClient.setQueryData(AUTH_QUERY_KEY, user)
      setToken(newToken)
      setSignedOut(false)
      return user
    },
    [queryClient],
  )

  const login = useCallback(async (credentials) => startSession(await authApi.login(credentials)), [startSession])

  const register = useCallback(async (payload) => startSession(await authApi.register(payload)), [startSession])

  // Signs out locally at once (so a reload can't resurrect the session), then
  // revokes the token on the server.
  const logout = useCallback(async () => {
    const currentToken = tokenStorage.get()
    setSignedOut(true)
    clearSession()
    if (!currentToken) return

    try {
      await authApi.logout(currentToken)
    } catch {
      // Already invalid or the API is unreachable; the token expires on its own.
    }
  }, [clearSession])

  /** Replace the cached user after a profile update. */
  const setUser = useCallback((user) => queryClient.setQueryData(AUTH_QUERY_KEY, user), [queryClient])

  const user = token ? (meQuery.data ?? null) : null

  let status
  if (!token) status = 'guest'
  else if (user) status = 'authenticated'
  else if (meQuery.isPending) status = 'loading'
  else status = 'error' // e.g. API unreachable while restoring the session

  const value = useMemo(
    () => ({
      user,
      status,
      isAuthenticated: status === 'authenticated',
      signedOut,
      error: meQuery.error,
      login,
      register,
      logout,
      setUser,
      retry: meQuery.refetch,
    }),
    [user, status, signedOut, meQuery.error, meQuery.refetch, login, register, logout, setUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
