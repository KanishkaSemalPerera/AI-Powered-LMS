import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { Link, useNavigate, useSearchParams } from 'react-router'
import { toast } from 'sonner'
import { z } from 'zod'
import { resetPassword } from '@/api/auth'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import PasswordInput from '@/components/ui/PasswordInput'
import { buttonClasses } from '@/components/ui/buttonStyles'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import { passwordSchema, withPasswordConfirmation } from '@/utils/validation'

const schema = withPasswordConfirmation(
  z.object({
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  }),
)

/** Opened from the emailed link: /reset-password?token=...&email=... */
export default function ResetPasswordPage() {
  useDocumentTitle('Choose a new password')
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const token = params.get('token') ?? ''
  const email = params.get('email') ?? ''

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema), defaultValues: { password: '', password_confirmation: '' } })

  if (!token || !email) {
    return (
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Invalid reset link</h1>
        <Alert variant="error" className="mt-6">
          This password reset link is incomplete. Please request a new one.
        </Alert>
        <Link to="/forgot-password" className={buttonClasses({ className: 'mt-6 w-full', size: 'lg' })}>
          Request a new link
        </Link>
      </div>
    )
  }

  const onSubmit = async (values) => {
    try {
      const message = await resetPassword({ ...values, token, email })
      toast.success(message)
      navigate('/login', { replace: true })
    } catch (error) {
      // An expired/used token comes back as a validation error on "email".
      const tokenProblem = error.response?.data?.errors?.email
      if (tokenProblem) {
        setError('root.server', { message: `${tokenProblem[0]} Please request a new link.` })
      } else if (error.response?.status !== 422 || !applyFieldErrors(error, setError)) {
        setError('root.server', { message: getErrorMessage(error) })
      }
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Choose a new password</h1>
      <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
        For security, you will be signed out of all devices after resetting.
      </p>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-8 space-y-5">
        {errors.root?.server && (
          <Alert variant="error">
            {errors.root.server.message}{' '}
            <Link to="/forgot-password" className="font-semibold underline">
              Request a new link
            </Link>
          </Alert>
        )}

        <FormField label="Email">
          <Input type="email" value={email} readOnly disabled />
        </FormField>

        <FormField
          label="New password"
          error={errors.password?.message}
          hint="At least 8 characters, including a letter and a number."
          required
        >
          <PasswordInput autoComplete="new-password" autoFocus {...register('password')} />
        </FormField>

        <FormField label="Confirm new password" error={errors.password_confirmation?.message} required>
          <PasswordInput autoComplete="new-password" {...register('password_confirmation')} />
        </FormField>

        <Button type="submit" size="lg" className="w-full" loading={isSubmitting}>
          Reset password
        </Button>
      </form>
    </div>
  )
}
