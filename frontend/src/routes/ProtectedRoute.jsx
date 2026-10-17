import { Navigate, Outlet, useLocation } from 'react-router'
import ErrorState from '@/components/ui/ErrorState'
import PageLoader from '@/components/ui/PageLoader'
import { useAuth } from '@/hooks/useAuth'

/**
 * Renders child routes only for signed-in users, optionally limited to roles.
 * The backend enforces the same rules; this guard is for user experience.
 *
 * <Route element={<ProtectedRoute roles={['instructor']} />}> ... </Route>
 */
export default function ProtectedRoute({ roles }) {
  const { status, user, signedOut, error, retry } = useAuth()
  const location = useLocation()

  if (status === 'loading') {
    return <PageLoader fullScreen label="Restoring your session" />
  }

  if (status === 'error') {
    return (
      <div className="mx-auto max-w-lg px-4 py-24">
        <ErrorState title="We couldn't restore your session" error={error} onRetry={() => retry()} />
      </div>
    )
  }

  if (status === 'guest') {
    // A deliberate logout goes home; an expired session or direct visit goes to login.
    return signedOut ? <Navigate to="/" replace /> : <Navigate to="/login" replace state={{ from: location }} />
  }

  if (roles && !roles.includes(user.role)) {
    return <Navigate to="/forbidden" replace />
  }

  return <Outlet />
}
