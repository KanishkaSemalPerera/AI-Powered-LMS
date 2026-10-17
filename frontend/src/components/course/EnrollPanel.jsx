import { useMutation, useQueryClient } from '@tanstack/react-query'
import { CirclePlay, Lock, Pencil, ShoppingCart } from 'lucide-react'
import { Link, useLocation, useNavigate } from 'react-router'
import { toast } from 'sonner'
import { enrollInCourse, startCheckout } from '@/api/enrollment'
import Button from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonStyles'
import ProgressBar from '@/components/ui/ProgressBar'
import { useAuth } from '@/hooks/useAuth'
import { getErrorMessage } from '@/utils/apiError'
import { continueLearningPath } from '@/utils/paths'
import { ROLES } from '@/utils/roles'

/** Price card call-to-action, depending on who is viewing and whether they're enrolled. */
export default function EnrollPanel({ course }) {
  const { user } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  const enroll = useMutation({
    mutationFn: () => enrollInCourse(course.id),
    onSuccess: ({ message }) => {
      toast.success(message)
      queryClient.invalidateQueries({ queryKey: ['course', course.slug] })
      queryClient.invalidateQueries({ queryKey: ['my-courses'] })
      navigate(continueLearningPath(course))
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const checkout = useMutation({
    mutationFn: () => startCheckout(course.id),
    // Leave the SPA for Stripe's hosted checkout page.
    onSuccess: ({ checkout_url }) => window.location.assign(checkout_url),
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  if (course.can_manage && user?.role === ROLES.INSTRUCTOR) {
    return (
      <Link to={`/instructor/courses/${course.id}/edit`} className={buttonClasses({ size: 'lg', className: 'w-full' })}>
        <Pencil className="size-4" aria-hidden="true" />
        Edit this course
      </Link>
    )
  }

  if (!user) {
    return (
      <Link to="/login" state={{ from: location }} className={buttonClasses({ size: 'lg', className: 'w-full' })}>
        Log in to enroll
      </Link>
    )
  }

  if (user.role !== ROLES.STUDENT) {
    return <p className="text-sm text-slate-500 dark:text-slate-400">Enrollment is available to student accounts.</p>
  }

  if (course.enrollment) {
    return (
      <div className="space-y-3">
        <ProgressBar value={course.enrollment.progress_percent} label="Your progress" showValue />
        <Link to={continueLearningPath(course)} className={buttonClasses({ size: 'lg', className: 'w-full' })}>
          <CirclePlay className="size-4" aria-hidden="true" />
          {course.enrollment.progress_percent > 0 ? 'Continue learning' : 'Start learning'}
        </Link>
      </div>
    )
  }

  if (course.is_free) {
    return (
      <Button size="lg" className="w-full" onClick={() => enroll.mutate()} loading={enroll.isPending}>
        Enroll for free
      </Button>
    )
  }

  return (
    <div className="space-y-2">
      <Button size="lg" className="w-full" onClick={() => checkout.mutate()} loading={checkout.isPending}>
        <ShoppingCart className="size-4" aria-hidden="true" />
        Buy now
      </Button>
      <p className="flex items-center justify-center gap-1 text-xs text-slate-500 dark:text-slate-400">
        <Lock className="size-3" aria-hidden="true" />
        Secure checkout with Stripe
      </p>
    </div>
  )
}
