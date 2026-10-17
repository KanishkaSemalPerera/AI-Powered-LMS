import { useQuery, useQueryClient } from '@tanstack/react-query'
import { CircleCheck, CirclePlay, Clock, TriangleAlert } from 'lucide-react'
import { useEffect } from 'react'
import { Link, useSearchParams } from 'react-router'
import { confirmCheckout } from '@/api/enrollment'
import { buttonClasses } from '@/components/ui/buttonStyles'
import Spinner from '@/components/ui/Spinner'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { getErrorMessage } from '@/utils/apiError'
import { continueLearningPath } from '@/utils/paths'

const MAX_POLLS = 10

/**
 * Stripe redirects here after payment: /checkout/success?session_id=cs_...
 * The page asks the API to confirm the payment (which also grants access if
 * the webhook hasn't arrived yet) and polls briefly while it is still pending.
 */
export default function CheckoutSuccessPage() {
  useDocumentTitle('Payment')
  const [params] = useSearchParams()
  const sessionId = params.get('session_id')
  const queryClient = useQueryClient()

  const query = useQuery({
    queryKey: ['checkout', sessionId],
    queryFn: () => confirmCheckout(sessionId),
    enabled: Boolean(sessionId),
    retry: false,
    refetchInterval: (q) =>
      q.state.data?.data.status === 'pending' && q.state.dataUpdateCount < MAX_POLLS ? 2000 : false,
  })

  const payment = query.data?.data
  const status = payment?.status

  useEffect(() => {
    if (status === 'paid') {
      queryClient.invalidateQueries({ queryKey: ['my-courses'] })
      queryClient.invalidateQueries({ queryKey: ['payments'] })
      if (payment.course) queryClient.invalidateQueries({ queryKey: ['course', payment.course.slug] })
    }
  }, [status, payment, queryClient])

  let icon = <Spinner className="size-12" label="Confirming payment" />
  let title = 'Confirming your payment…'
  let text = 'This usually takes a few seconds.'
  let actions = null

  if (!sessionId) {
    icon = <TriangleAlert className="size-14 text-amber-500" aria-hidden="true" />
    title = 'Missing payment details'
    text = 'This page should be opened from the Stripe checkout. Check your payment history instead.'
    actions = (
      <Link to="/payments" className={buttonClasses()}>
        Payment history
      </Link>
    )
  } else if (query.error) {
    icon = <TriangleAlert className="size-14 text-red-500" aria-hidden="true" />
    title = 'We couldn’t confirm this payment'
    text = getErrorMessage(query.error)
    actions = (
      <Link to="/payments" className={buttonClasses({ variant: 'outline' })}>
        Payment history
      </Link>
    )
  } else if (status === 'paid') {
    icon = <CircleCheck className="size-16 text-emerald-500" aria-hidden="true" />
    title = 'You’re enrolled!'
    text = `Payment received for “${payment.course?.title}”. Your course is ready.`
    actions = (
      <>
        <Link to={continueLearningPath(payment.course)} className={buttonClasses({ size: 'lg' })}>
          <CirclePlay className="size-4" aria-hidden="true" />
          Go to my courses
        </Link>
      </>
    )
  } else if (status === 'pending' && query.data && query.dataUpdateCount >= MAX_POLLS) {
    icon = <Clock className="size-14 text-amber-500" aria-hidden="true" />
    title = 'Payment is still processing'
    text = 'Some payment methods take longer to confirm. We’ll enroll you automatically as soon as it clears.'
    actions = (
      <Link to="/my-courses" className={buttonClasses()}>
        Go to my courses
      </Link>
    )
  } else if (status && status !== 'pending') {
    icon = <TriangleAlert className="size-14 text-red-500" aria-hidden="true" />
    title = 'Payment not completed'
    text = query.data.message
    actions = payment.course && (
      <Link to={`/courses/${payment.course.slug}`} className={buttonClasses()}>
        Back to the course
      </Link>
    )
  }

  return (
    <div className="mx-auto flex max-w-lg flex-col items-center px-4 py-24 text-center" aria-live="polite">
      {icon}
      <h1 className="mt-6 text-3xl font-bold tracking-tight">{title}</h1>
      <p className="mt-3 text-slate-600 dark:text-slate-400">{text}</p>
      {actions && <div className="mt-8 flex flex-wrap justify-center gap-3">{actions}</div>}
    </div>
  )
}
