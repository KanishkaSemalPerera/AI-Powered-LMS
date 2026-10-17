import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { Camera, Trash2 } from 'lucide-react'
import { useRef } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import * as profileApi from '@/api/profile'
import Avatar from '@/components/ui/Avatar'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Card, { CardBody, CardHeader } from '@/components/ui/Card'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import PasswordInput from '@/components/ui/PasswordInput'
import Textarea from '@/components/ui/Textarea'
import { useAuth } from '@/hooks/useAuth'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import {
  AVATAR_MAX_BYTES,
  AVATAR_TYPES,
  emailSchema,
  nameSchema,
  passwordSchema,
  withPasswordConfirmation,
} from '@/utils/validation'

// ------------------------------------------------------------------ Photo

function AvatarCard() {
  const { user, setUser } = useAuth()
  const fileInput = useRef(null)

  const upload = useMutation({
    mutationFn: profileApi.uploadAvatar,
    onSuccess: (updated) => {
      setUser(updated)
      toast.success('Profile photo updated.')
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const remove = useMutation({
    mutationFn: profileApi.removeAvatar,
    onSuccess: (updated) => {
      setUser(updated)
      toast.success('Profile photo removed.')
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  const onFileChange = (event) => {
    const file = event.target.files?.[0]
    event.target.value = '' // allow choosing the same file again
    if (!file) return

    // Quick client-side checks; the server validates the real file contents.
    if (!AVATAR_TYPES.includes(file.type)) {
      toast.error('Please choose a JPG, PNG or WebP image.')
      return
    }
    if (file.size > AVATAR_MAX_BYTES) {
      toast.error('The image must be smaller than 2 MB.')
      return
    }
    upload.mutate(file)
  }

  return (
    <Card aria-labelledby="photo-heading">
      <CardHeader id="photo-heading" title="Profile photo" description="JPG, PNG or WebP, up to 2 MB." />
      <CardBody className="flex flex-col items-center gap-5 sm:flex-row">
        <Avatar src={user.avatar_url} name={user.name} size="xl" />
        <div className="flex flex-wrap justify-center gap-2">
          <input
            ref={fileInput}
            type="file"
            accept={AVATAR_TYPES.join(',')}
            className="sr-only"
            onChange={onFileChange}
            aria-label="Choose a profile photo"
          />
          <Button onClick={() => fileInput.current?.click()} loading={upload.isPending}>
            <Camera className="size-4" aria-hidden="true" />
            {user.avatar_url ? 'Change photo' : 'Upload photo'}
          </Button>
          {user.avatar_url && (
            <Button variant="outline" onClick={() => remove.mutate()} loading={remove.isPending}>
              <Trash2 className="size-4" aria-hidden="true" />
              Remove
            </Button>
          )}
        </div>
      </CardBody>
    </Card>
  )
}

// ------------------------------------------------------------------ Details

const detailsSchema = z.object({
  name: nameSchema,
  email: emailSchema,
  headline: z.string().trim().max(120, 'Headline must be at most 120 characters'),
  bio: z.string().trim().max(2000, 'Bio must be at most 2000 characters'),
})

function DetailsCard() {
  const { user, setUser } = useAuth()

  const {
    register,
    handleSubmit,
    setError,
    reset,
    formState: { errors, isDirty, isSubmitting },
  } = useForm({
    resolver: zodResolver(detailsSchema),
    defaultValues: { name: user.name, email: user.email, headline: user.headline ?? '', bio: user.bio ?? '' },
  })

  const onSubmit = async (values) => {
    try {
      const updated = await profileApi.updateProfile(values)
      setUser(updated)
      reset({ name: updated.name, email: updated.email, headline: updated.headline ?? '', bio: updated.bio ?? '' })
      toast.success('Profile updated.')
    } catch (error) {
      if (!applyFieldErrors(error, setError)) toast.error(getErrorMessage(error))
    }
  }

  return (
    <Card aria-labelledby="details-heading">
      <CardHeader id="details-heading" title="Personal details" description="Shown to instructors and on your reviews." />
      <CardBody>
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <div className="grid gap-5 sm:grid-cols-2">
            <FormField label="Full name" error={errors.name?.message} required>
              <Input autoComplete="name" {...register('name')} />
            </FormField>
            <FormField label="Email" error={errors.email?.message} required>
              <Input type="email" autoComplete="email" {...register('email')} />
            </FormField>
          </div>
          <FormField label="Headline" error={errors.headline?.message} hint="For example: Frontend developer, lifelong learner">
            <Input {...register('headline')} />
          </FormField>
          <FormField label="Bio" error={errors.bio?.message}>
            <Textarea rows={5} {...register('bio')} />
          </FormField>
          <div className="flex justify-end">
            <Button type="submit" loading={isSubmitting} disabled={!isDirty}>
              Save changes
            </Button>
          </div>
        </form>
      </CardBody>
    </Card>
  )
}

// ------------------------------------------------------------------ Password

const passwordFormSchema = withPasswordConfirmation(
  z.object({
    current_password: z.string().min(1, 'Enter your current password'),
    password: passwordSchema,
    password_confirmation: z.string().min(1, 'Please confirm your new password'),
  }),
)

function PasswordCard() {
  const {
    register,
    handleSubmit,
    setError,
    reset,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(passwordFormSchema),
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  })

  const onSubmit = async (values) => {
    try {
      toast.success(await profileApi.changePassword(values))
      reset()
    } catch (error) {
      if (!applyFieldErrors(error, setError)) toast.error(getErrorMessage(error))
    }
  }

  return (
    <Card aria-labelledby="password-heading">
      <CardHeader
        id="password-heading"
        title="Change password"
        description="Other devices will be signed out after the change."
      />
      <CardBody>
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
          <FormField label="Current password" error={errors.current_password?.message} required>
            <PasswordInput autoComplete="current-password" {...register('current_password')} />
          </FormField>
          <div className="grid gap-5 sm:grid-cols-2">
            <FormField
              label="New password"
              error={errors.password?.message}
              hint="At least 8 characters, with a letter and a number."
              required
            >
              <PasswordInput autoComplete="new-password" {...register('password')} />
            </FormField>
            <FormField label="Confirm new password" error={errors.password_confirmation?.message} required>
              <PasswordInput autoComplete="new-password" {...register('password_confirmation')} />
            </FormField>
          </div>
          <div className="flex justify-end">
            <Button type="submit" loading={isSubmitting}>
              Update password
            </Button>
          </div>
        </form>
      </CardBody>
    </Card>
  )
}

// ------------------------------------------------------------------ Page

export default function ProfilePage() {
  useDocumentTitle('Profile')
  const { user } = useAuth()

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <h1 className="text-2xl font-bold tracking-tight">Your profile</h1>
        <Badge variant="brand">{user.role_label}</Badge>
      </div>
      <AvatarCard />
      <DetailsCard />
      <PasswordCard />
    </div>
  )
}
