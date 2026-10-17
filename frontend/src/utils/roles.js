export const ROLES = Object.freeze({
  STUDENT: 'student',
  INSTRUCTOR: 'instructor',
  ADMIN: 'admin',
})

const DASHBOARDS = {
  [ROLES.STUDENT]: '/dashboard',
  [ROLES.INSTRUCTOR]: '/instructor',
  [ROLES.ADMIN]: '/admin',
}

/** Home page of the signed-in area for a role. */
export function dashboardPath(role) {
  return DASHBOARDS[role] ?? '/'
}
