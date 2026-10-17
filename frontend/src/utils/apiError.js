// Helpers for reading the backend's error envelope:
// { success: false, message, data, errors: { field: [messages] }, meta }

/** Human-readable message for any error thrown by the API client. */
export function getErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  if (!error) return fallback
  if (error.response?.data?.message) return error.response.data.message
  if (error.code === 'ECONNABORTED') return 'The server took too long to respond. Please try again.'
  if (error.request && !error.response) {
    return 'Cannot reach the server. Check your connection or that the API is running.'
  }
  return error.message || fallback
}

/** Field-level validation errors as { field: 'first message' } for forms. */
export function getFieldErrors(error) {
  const errors = error?.response?.data?.errors
  if (!errors || typeof errors !== 'object') return {}

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? messages[0] : String(messages),
    ]),
  )
}

/**
 * Copies backend validation errors onto a React Hook Form instance so they
 * appear under the matching inputs. Returns true when any were applied.
 */
export function applyFieldErrors(error, setError) {
  const fieldErrors = getFieldErrors(error)
  Object.entries(fieldErrors).forEach(([field, message]) => {
    setError(field, { type: 'server', message })
  })
  return Object.keys(fieldErrors).length > 0
}
