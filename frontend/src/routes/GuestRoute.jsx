import { Navigate, Outlet, useLocation } from 'react-router'
import PageLoader from '@/components/ui/PageLoader'
import { useAuth } from '@/hooks/useAuth'
import { dashboardPath } from '@/utils/roles'

/**
 * For login/register pages: signed-in users are sent on to the page they
 * originally wanted (if any) or to their dashboard. This also performs the
 * redirect right after a successful login or registration.
 */
export default function GuestRoute() {
  const { status, user } = useAuth()
  const location = useLocation()

  if (status === 'loading') {
    return <PageLoader fullScreen />
  }

  if (status === 'authenticated') {
    const from = location.state?.from
    const target = from ? `${from.pathname}${from.search ?? ''}` : dashboardPath(user.role)
    return <Navigate to={target} replace />
  }

  return <Outlet />
}
