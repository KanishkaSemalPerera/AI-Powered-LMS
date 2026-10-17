import { CircleX } from 'lucide-react'
import { Link, useSearchParams } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonStyles'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'

/** Stripe sends the student here when they leave checkout without paying. */
export default function CheckoutCancelPage() {
  useDocumentTitle('Checkout cancelled')
  const [params] = useSearchParams()
  const slug = params.get('course')

  return (
    <div className="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center">
      <CircleX className="size-14 text-slate-400" aria-hidden="true" />
      <h1 className="mt-6 text-3xl font-bold tracking-tight">Checkout cancelled</h1>
      <p className="mt-3 text-slate-600 dark:text-slate-400">No payment was taken. You can return to the course whenever you&apos;re ready.</p>
      <div className="mt-8 flex flex-wrap justify-center gap-3">
        {slug && (
          <Link to={`/courses/${encodeURIComponent(slug)}`} className={buttonClasses()}>
            Back to the course
          </Link>
        )}
        <Link to="/courses" className={buttonClasses({ variant: 'outline' })}>
          Browse courses
        </Link>
      </div>
    </div>
  )
}
