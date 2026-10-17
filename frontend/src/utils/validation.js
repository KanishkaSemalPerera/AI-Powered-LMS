import { z } from 'zod'

// Client-side rules mirror the Laravel Form Requests, so most mistakes are
// caught before a request is sent. The server remains the source of truth.

export const emailSchema = z
  .string()
  .trim()
  .min(1, 'Email is required')
  .max(255, 'Email is too long')
  .pipe(z.email('Enter a valid email address'))

export const nameSchema = z
  .string()
  .trim()
  .min(2, 'Name must be at least 2 characters')
  .max(100, 'Name must be at most 100 characters')

/** Matches Password::defaults() in AppServiceProvider (min 8, letters and numbers). */
export const passwordSchema = z
  .string()
  .min(8, 'Password must be at least 8 characters')
  .regex(/[A-Za-z]/, 'Password must contain at least one letter')
  .regex(/\d/, 'Password must contain at least one number')

/** Adds a "password_confirmation" must-match check to an object schema. */
export function withPasswordConfirmation(schema, field = 'password') {
  return schema.refine((values) => values[field] === values.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  })
}

export const AVATAR_MAX_BYTES = 2 * 1024 * 1024
export const AVATAR_TYPES = ['image/jpeg', 'image/png', 'image/webp']
