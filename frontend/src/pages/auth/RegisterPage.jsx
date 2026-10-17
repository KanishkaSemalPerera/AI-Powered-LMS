import { zodResolver } from '@hookform/resolvers/zod'
import { GraduationCap, Presentation } from 'lucide-react'
import { useForm, useWatch } from 'react-hook-form'
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
import { cn } from '@/utils/cn'
import { ROLES } from '@/utils/roles'
import { emailSchema, nameSchema, passwordSchema, withPasswordConfirmation } from '@/utils/validation'

const schema = withPasswordConfirmation(
  z.object({
    name: nameSchema,
    email: emailSchema,
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'Please confirm your password'),
    role: z.enum([ROLES.STUDENT, ROLES.INSTRUCTOR], { error: 'Choose how you want to use the platform' }),
  }),
)

const roleOptions = [
  { value: ROLES.STUDENT, title: 'I want to learn', text: 'Enroll in courses and track progress', icon: GraduationCap },
  { value: ROLES.INSTRUCTOR, title: 'I want to teach', text: 'Create courses and reach students', icon: Presentation },
]

export default function RegisterPage() {
  useDocumentTitle('Create your account')
  const { register: registerAccount } = useAuth()

  const {
    register,
    handleSubmit,
    setError,
    control,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', password: '', password_confirmation: '', role: ROLES.STUDENT },
  })

  const selectedRole = useWatch({ control, name: 'role' })

  const onSubmit = async (values) => {
    try {
      const user = await registerAccount(values)
      toast.success(`Welcome, ${user.name.split(' ')[0]}! Your account is ready.`)
    } catch (error) {
      if (error.response?.status !== 422 || !applyFieldErrors(error, setError)) {
        setError('root.server', { message: getErrorMessage(error) })
      }
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Create your account</h1>
      <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
        Already have an account?{' '}
        <Link to="/login" className="font-medium text-brand-600 hover:underline dark:text-brand-400">
          Log in
        </Link>
      </p>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-8 space-y-5">
        {errors.root?.server && <Alert variant="error">{errors.root.server.message}</Alert>}

        <fieldset>
          <legend className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">Join as</legend>
          <div className="grid gap-3 sm:grid-cols-2">
            {roleOptions.map(({ value, title, text, icon: Icon }) => (
              <label
                key={value}
                className={cn(
                  'flex cursor-pointer gap-3 rounded-xl border p-4 transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-brand-500',
                  selectedRole === value
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50'
                    : 'border-slate-300 hover:border-slate-400 dark:border-slate-700',
                )}
              >
                <input type="radio" value={value} className="sr-only" {...register('role')} />
                <Icon
                  className={cn('mt-0.5 size-5 shrink-0', selectedRole === value ? 'text-brand-600' : 'text-slate-400')}
                  aria-hidden="true"
                />
                <span>
                  <span className="block text-sm font-semibold">{title}</span>
                  <span className="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{text}</span>
                </span>
              </label>
            ))}
          </div>
          {errors.role && (
            <p role="alert" className="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">
              {errors.role.message}
            </p>
          )}
        </fieldset>

        <FormField label="Full name" error={errors.name?.message} required>
          <Input autoComplete="name" {...register('name')} />
        </FormField>

        <FormField label="Email" error={errors.email?.message} required>
          <Input type="email" autoComplete="email" {...register('email')} />
        </FormField>

        <FormField
          label="Password"
          error={errors.password?.message}
          hint="At least 8 characters, including a letter and a number."
          required
        >
          <PasswordInput autoComplete="new-password" {...register('password')} />
        </FormField>

        <FormField label="Confirm password" error={errors.password_confirmation?.message} required>
          <PasswordInput autoComplete="new-password" {...register('password_confirmation')} />
        </FormField>

        <Button type="submit" size="lg" className="w-full" loading={isSubmitting}>
          Create account
        </Button>
      </form>
    </div>
  )
}
