import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { toast } from 'sonner'
import { z } from 'zod'
import Alert from '@/components/ui/Alert'
import Button from '@/components/ui/Button'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import PasswordInput from '@/components/ui/PasswordInput'
import { useAuth } from '@/hooks/useAuth'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import { emailSchema } from '@/utils/validation'

const schema = z.object({
  email: emailSchema,
  password: z.string().min(1, 'Password is required'),
})

// Seeded demo accounts (database/seeders/UserSeeder.php), shown in development only.
const demoAccounts = [
  { label: 'Student', email: 'student1@lms.test' },
  { label: 'Instructor', email: 'instructor1@lms.test' },
  { label: 'Admin', email: 'admin@lms.test' },
]

export default function LoginPage() {
  useDocumentTitle('Log in')
  const { login } = useAuth()

  const {
    register,
    handleSubmit,
    setError,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm({ resolver: zodResolver(schema), defaultValues: { email: '', password: '' } })

  // On success GuestRoute redirects to the requested page or the user's dashboard.
  const onSubmit = async (values) => {
    try {
      const user = await login(values)
      toast.success(`Welcome back, ${user.name.split(' ')[0]}!`)
    } catch (error) {
      // 422 → messages under fields; 403 (deactivated) / 429 (throttled) / network → banner.
      if (error.response?.status !== 422 || !applyFieldErrors(error, setError)) {
        setError('root.server', { message: getErrorMessage(error) })
      }
    }
  }

  const fillDemo = (email) => {
    setValue('email', email, { shouldValidate: true })
    setValue('password', 'password', { shouldValidate: true })
  }

  return (
    <div>
      <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Welcome back</h1>
      <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
        Don&apos;t have an account?{' '}
        <Link to="/register" className="font-medium text-brand-600 hover:underline dark:text-brand-400">
          Sign up for free
        </Link>
      </p>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-8 space-y-5">
        {errors.root?.server && <Alert variant="error">{errors.root.server.message}</Alert>}

        <FormField label="Email" error={errors.email?.message} required>
          <Input type="email" autoComplete="email" autoFocus {...register('email')} />
        </FormField>

        <div>
          <FormField label="Password" error={errors.password?.message} required>
            <PasswordInput autoComplete="current-password" {...register('password')} />
          </FormField>
          <div className="mt-2 text-right">
            <Link to="/forgot-password" className="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
              Forgot your password?
            </Link>
          </div>
        </div>

        <Button type="submit" size="lg" className="w-full" loading={isSubmitting}>
          Log in
        </Button>
      </form>

      {import.meta.env.DEV && (
        <div className="mt-8 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Demo accounts (development only)</p>
          <div className="mt-3 flex flex-wrap gap-2">
            {demoAccounts.map((account) => (
              <Button key={account.email} variant="outline" size="sm" onClick={() => fillDemo(account.email)}>
                {account.label}
              </Button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
