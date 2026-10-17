import { ShieldX } from 'lucide-react'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonStyles'
import { useAuth } from '@/hooks/useAuth'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { dashboardPath } from '@/utils/roles'

/** Shown when a signed-in user opens a page meant for another role. */
export default function ForbiddenPage() {
  useDocumentTitle('Access denied')
  const { user } = useAuth()

  return (
    <div className="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
      <ShieldX className="size-14 text-red-500" aria-hidden="true" />
      <p className="mt-6 text-sm font-semibold text-red-600 dark:text-red-400">403</p>
      <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Access denied</h1>
      <p className="mt-4 text-slate-600 dark:text-slate-400">
        Your account doesn&apos;t have permission to view this page.
      </p>
      <Link to={user ? dashboardPath(user.role) : '/'} className={buttonClasses({ className: 'mt-8' })}>
        {user ? 'Go to my dashboard' : 'Back to home'}
      </Link>
    </div>
  )
}
