import client, { unwrap } from './client'

/** payload: { name, email, headline, bio } → updated user */
export async function updateProfile(payload) {
  return unwrap(await client.put('/profile', payload)).data
}

/** Uploads an image File as multipart/form-data → updated user */
export async function uploadAvatar(file) {
  const form = new FormData()
  form.append('avatar', file)
  return unwrap(await client.post('/profile/avatar', form)).data
}

export async function removeAvatar() {
  return unwrap(await client.delete('/profile/avatar')).data
}

/** payload: { current_password, password, password_confirmation } */
export async function changePassword(payload) {
  return unwrap(await client.put('/profile/password', payload)).message
}
