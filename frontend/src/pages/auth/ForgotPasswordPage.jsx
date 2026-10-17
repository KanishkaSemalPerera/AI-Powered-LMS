import { zodResolver } from '@hookform/resolvers/zod'
import { ArrowLeft, MailCheck } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { z } from 'zod'
import { forgotPassword } from '@/api/auth'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import { emailSchema } from '@/utils/validation'

const schema = z.object({ email: emailSchema })

export default function ForgotPasswordPage() {
  useDocumentTitle('Forgot password')
  const [sentMessage, setSentMessage] = useState(null)

  const {
    register,
    handleSubmit,
    setError,
    getValues,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema), defaultValues: { email: '' } })

  const onSubmit = async ({ email }) => {
    try {
      setSentMessage(await forgotPassword(email))
    } catch (error) {
      if (error.response?.status !== 422 || !applyFieldErrors(error, setError)) {
        setError('root.server', { message: getErrorMessage(error) })
      }
    }
  }

  if (sentMessage) {
    return (
      <div className="text-center">
        <MailCheck className="mx-auto size-12 text-brand-600" aria-hidden="true" />
        <h1 className="mt-4 text-2xl font-bold tracking-tight">Check your inbox</h1>
        <p className="mt-3 text-sm text-slate-600 dark:text-slate-400">{sentMessage}</p>
        <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
          We sent it to <span className="font-medium text-slate-900 dark:text-white">{getValues('email')}</span>. The link
          expires in 60 minutes.
        </p>
        {import.meta.env.DEV && (
          <Alert variant="info" className="mt-6 text-left">
            Development: emails are written to <code>backend/storage/logs/laravel.log</code> — open it and copy the reset link.
          </Alert>
        )}
        <Link to="/login" className="mt-8 inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
          <ArrowLeft className="size-4" aria-hidden="true" />
          Back to log in
        </Link>
      </div>
    )
  }

  return (
    <div>
      <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Forgot your password?</h1>
      <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
        Enter your account email and we&apos;ll send you a link to choose a new password.
      </p>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-8 space-y-5">
        {errors.root?.server && <Alert variant="error">{errors.root.server.message}</Alert>}

        <FormField label="Email" error={errors.email?.message} required>
          <Input type="email" autoComplete="email" autoFocus {...register('email')} />
        </FormField>

        <Button type="submit" size="lg" className="w-full" loading={isSubmitting}>
          Send reset link
        </Button>
      </form>

      <Link to="/login" className="mt-6 inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
        <ArrowLeft className="size-4" aria-hidden="true" />
        Back to log in
      </Link>
    </div>
  )
}
