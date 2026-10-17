import client, { unwrap } from './client'

/** @returns {Promise<{ user, token, token_type, expires_at }>} */
export async function register(payload) {
  return unwrap(await client.post('/auth/register', payload)).data
}

/** @returns {Promise<{ user, token, token_type, expires_at }>} */
export async function login(credentials) {
  return unwrap(await client.post('/auth/login', credentials)).data
}

/** Revokes the given token on the server (passed explicitly because local storage is cleared first). */
export async function logout(token) {
  await client.post('/auth/logout', null, { headers: { Authorization: `Bearer ${token}` } })
}

export async function getMe() {
  return unwrap(await client.get('/auth/me')).data
}

/** @returns {Promise<string>} the confirmation message */
export async function forgotPassword(email) {
  return unwrap(await client.post('/auth/forgot-password', { email })).message
}

/** payload: { token, email, password, password_confirmation } */
export async function resetPassword(payload) {
  return unwrap(await client.post('/auth/reset-password', payload)).message
}
