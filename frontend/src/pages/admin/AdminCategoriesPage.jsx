import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { FolderOpen, Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'
import { createCategory, deleteCategory, getAdminCategories, updateCategory } from '@/api/admin'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import EmptyState from '@/components/ui/EmptyState'
import ErrorState from '@/components/ui/ErrorState'
import FormField from '@/components/ui/FormField'
import Input from '@/components/ui/Input'
import Modal from '@/components/ui/Modal'
import Select from '@/components/ui/Select'
import Skeleton from '@/components/ui/Skeleton'
import Textarea from '@/components/ui/Textarea'
import { useDocumentTitle } from '@/hooks/useDocumentTitle'
import { applyFieldErrors, getErrorMessage } from '@/utils/apiError'
import CategoryIcon from '@/components/ui/CategoryIcon'
import { CATEGORY_ICONS } from '@/utils/categoryIcons'
import { plural } from '@/utils/format'

const schema = z.object({
  name: z.string().trim().min(2, 'Name must be at least 2 characters').max(100),
  description: z.string().trim().max(500, 'Description must be at most 500 characters'),
  icon: z.string(),
})

const iconOptions = Object.keys(CATEGORY_ICONS).map((name) => ({ value: name, label: name.replace('-', ' ') }))

function CategoryModal({ category, onClose }) {
  const queryClient = useQueryClient()
  const isNew = !category?.id

  const {
    register,
    handleSubmit,
    setError,
    control,
    formState: { errors, isSubmitting },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: category?.name ?? '', description: category?.description ?? '', icon: category?.icon ?? 'book-open' },
  })
  const selectedIcon = useWatch({ control, name: 'icon' })

  const onSubmit = async (values) => {
    try {
      const payload = { ...values, icon: values.icon || null }
      const { message } = isNew ? await createCategory(payload) : await updateCategory(category.id, payload)
      toast.success(message)
      queryClient.invalidateQueries({ queryKey: ['admin', 'categories'] })
      queryClient.invalidateQueries({ queryKey: ['categories'] })
      onClose()
    } catch (error) {
      if (!applyFieldErrors(error, setError)) toast.error(getErrorMessage(error))
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={isNew ? 'New category' : 'Edit category'}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={isSubmitting}>
            Cancel
          </Button>
          <Button type="submit" form="category-form" loading={isSubmitting}>
            {isNew ? 'Create category' : 'Save changes'}
          </Button>
        </>
      }
    >
      <form id="category-form" onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
        <FormField label="Name" error={errors.name?.message} required>
          <Input autoFocus {...register('name')} />
        </FormField>
        <FormField label="Icon" error={errors.icon?.message}>
          <div className="flex items-center gap-3">
            <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
              <CategoryIcon name={selectedIcon} className="size-5" aria-hidden="true" />
            </span>
            <div className="flex-1">
              <Select options={iconOptions} aria-label="Icon" {...register('icon')} />
            </div>
          </div>
        </FormField>
        <FormField label="Description" error={errors.description?.message}>
          <Textarea rows={3} {...register('description')} />
        </FormField>
      </form>
    </Modal>
  )
}

export default function AdminCategoriesPage() {
  useDocumentTitle('Categories')
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null) // {} for a new category
  const [toDelete, setToDelete] = useState(null)

  const query = useQuery({ queryKey: ['admin', 'categories'], queryFn: getAdminCategories })

  const remove = useMutation({
    mutationFn: (category) => deleteCategory(category.id),
    onSuccess: ({ message }) => {
      toast.success(message)
      setToDelete(null)
      queryClient.invalidateQueries({ queryKey: ['admin', 'categories'] })
      queryClient.invalidateQueries({ queryKey: ['categories'] })
    },
    onError: (error) => toast.error(getErrorMessage(error)),
  })

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">Categories</h1>
          <p className="text-sm text-slate-500 dark:text-slate-400">Organise the course catalogue.</p>
        </div>
        <Button onClick={() => setEditing({})}>
          <Plus className="size-4" aria-hidden="true" />
          New category
        </Button>
      </div>

      {query.isPending ? (
        <Skeleton className="h-72 w-full" />
      ) : query.error ? (
        <ErrorState title="Couldn't load categories" error={query.error} onRetry={() => query.refetch()} />
      ) : query.data.length === 0 ? (
        <EmptyState icon={FolderOpen} title="No categories yet" description="Create the first category for your catalogue." />
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="w-full min-w-[36rem] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
              <tr>
                <th scope="col" className="px-4 py-3 font-medium">Category</th>
                <th scope="col" className="px-4 py-3 font-medium">Published courses</th>
                <th scope="col" className="px-4 py-3 font-medium">
                  <span className="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
              {query.data.map((category) => {
                return (
                  <tr key={category.id} className="bg-white dark:bg-slate-950">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                          <CategoryIcon name={category.icon} className="size-4" aria-hidden="true" />
                        </span>
                        <div className="min-w-0">
                          <p className="font-medium">{category.name}</p>
                          {category.description && (
                            <p className="line-clamp-1 text-xs text-slate-500 dark:text-slate-400">{category.description}</p>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-300">{plural(category.courses_count ?? 0, 'course')}</td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-1">
                        <Button variant="ghost" size="sm" onClick={() => setEditing(category)} aria-label={`Edit ${category.name}`}>
                          <Pencil className="size-4" aria-hidden="true" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                          onClick={() => setToDelete(category)}
                          aria-label={`Delete ${category.name}`}
                        >
                          <Trash2 className="size-4" aria-hidden="true" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}

      {editing && <CategoryModal category={editing} onClose={() => setEditing(null)} />}

      <ConfirmDialog
        open={Boolean(toDelete)}
        onClose={() => setToDelete(null)}
        onConfirm={() => remove.mutate(toDelete)}
        loading={remove.isPending}
        variant="danger"
        confirmLabel="Delete category"
        title="Delete this category?"
        message={`Courses in “${toDelete?.name}” will become uncategorised. This cannot be undone.`}
      />
    </div>
  )
}
