import { Compass, House } from 'lucide-react'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonStyles'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'

export default function NotFoundPage() {
  useDocumentTitle('Page not found')

  return (
    <div className="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
      <Compass className="size-14 text-brand-500" aria-hidden="true" />
      <p className="mt-6 text-sm font-semibold text-brand-600 dark:text-brand-400">404</p>
      <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Page not found</h1>
      <p className="mt-4 text-slate-600 dark:text-slate-400">
        The page you are looking for doesn't exist or has been moved.
      </p>
      <Link to="/" className={buttonClasses({ className: 'mt-8' })}>
        <House className="size-4" aria-hidden="true" />
        Back to home
      </Link>
    </div>
  )
}
