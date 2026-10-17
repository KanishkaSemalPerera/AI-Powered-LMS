import client, { unwrap } from './client'

/** GET /health — API and database status. */
export async function getHealth() {
  const response = await client.get('/health')
  return unwrap(response).data
}
