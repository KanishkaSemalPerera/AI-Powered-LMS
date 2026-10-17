import axios from 'axios'
import { tokenStorage } from '@/utils/storage'

export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'

/**
 * Fired when the API rejects our token (expired, revoked or account deactivated),
 * so AuthProvider can sign the user out. event.detail.reason: 'expired' | 'deactivated'.
 */
export const UNAUTHORIZED_EVENT = 'lms:unauthorized'

const client = axios.create({
  baseURL: API_URL,
  timeout: 30000,
  headers: {
    Accept: 'application/json',
  },
})

// Attach the Sanctum Bearer token to every request when the user is logged in.
client.interceptors.request.use((config) => {
  const token = tokenStorage.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    const hadToken = Boolean(error.config?.headers?.Authorization)
    const status = error.response?.status
    const deactivated = status === 403 && Boolean(error.response?.data?.errors?.account)

    // A 401 on a request that carried a token means it expired or was revoked.
    if (hadToken && (status === 401 || deactivated)) {
      tokenStorage.clear()
      window.dispatchEvent(
        new CustomEvent(UNAUTHORIZED_EVENT, { detail: { reason: deactivated ? 'deactivated' : 'expired' } }),
      )
    }
    return Promise.reject(error)
  },
)

/**
 * Unwraps the standard envelope so callers get { data, meta, message }
 * instead of the raw Axios response.
 */
export function unwrap(response) {
  const body = response.data ?? {}
  return { data: body.data, meta: body.meta, message: body.message }
}

export default client
