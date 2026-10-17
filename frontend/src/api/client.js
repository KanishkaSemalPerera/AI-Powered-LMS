import axios from 'axios'
import { tokenStorage } from '@/utils/storage'

export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'

/** Event fired when the API rejects our token, so AuthContext can log the user out. */
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

// A 401 on a request that carried a token means it expired or was revoked.
client.interceptors.response.use(
  (response) => response,
  (error) => {
    const hadToken = Boolean(error.config?.headers?.Authorization)
    if (error.response?.status === 401 && hadToken) {
      tokenStorage.clear()
      window.dispatchEvent(new CustomEvent(UNAUTHORIZED_EVENT))
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
