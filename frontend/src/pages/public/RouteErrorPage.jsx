import { TriangleAlert } from 'lucide-react'
import { isRouteErrorResponse, useRouteError } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonStyles'
import NotFoundPage from './NotFoundPage'

/** Shown when a route component throws during render, so the app never goes blank. */
export default function RouteErrorPage() {
  const error = useRouteError()

  if (isRouteErrorResponse(error) && error.status === 404) {
    return <NotFoundPage />
  }

  return (
    <div className="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-4 text-center">
      <TriangleAlert className="size-14 text-red-500" aria-hidden="true" />
      <h1 className="mt-6 text-3xl font-bold tracking-tight">Something went wrong</h1>
      <p className="mt-4 text-slate-600 dark:text-slate-400">
        An unexpected error occurred. Please reload the page or go back home.
      </p>
      {import.meta.env.DEV && error instanceof Error && (
        <pre className="mt-6 max-w-full overflow-x-auto rounded-lg bg-slate-100 p-4 text-left text-xs dark:bg-slate-900">
          {error.message}
        </pre>
      )}
      <a href="/" className={buttonClasses({ className: 'mt-8' })}>
        Back to home
      </a>
    </div>
  )
}
