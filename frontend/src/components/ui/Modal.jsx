import { X } from 'lucide-react'
import { useEffect, useId, useRef } from 'react'
import { cn } from '@/utils/cn'

/**
 * Accessible modal built on the native <dialog> element, which provides
 * focus trapping, Escape-to-close and the top layer for free.
 */
export default function Modal({ open, onClose, title, description, children, footer, size = 'md' }) {
  const dialogRef = useRef(null)
  const titleId = useId()
  const descriptionId = useId()

  useEffect(() => {
    const dialog = dialogRef.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal()
    if (!open && dialog.open) dialog.close()
  }, [open])

  return (
    <dialog
      ref={dialogRef}
      aria-labelledby={titleId}
      aria-describedby={description ? descriptionId : undefined}
      onClose={onClose}
      onCancel={(event) => {
        event.preventDefault()
        onClose()
      }}
      // Clicking the backdrop (the dialog element itself, outside the panel) closes it.
      onClick={(event) => event.target === dialogRef.current && onClose()}
      className={cn(
        'm-auto w-[calc(100%-2rem)] rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-xl',
        'backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100',
        size === 'sm' ? 'max-w-md' : size === 'lg' ? 'max-w-2xl' : 'max-w-lg',
      )}
    >
      {open && (
        <div>
          <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div>
              <h2 id={titleId} className="text-lg font-semibold">
                {title}
              </h2>
              {description && (
                <p id={descriptionId} className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  {description}
                </p>
              )}
            </div>
            <button
              type="button"
              onClick={onClose}
              className="rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
              aria-label="Close dialog"
            >
              <X className="size-5" aria-hidden="true" />
            </button>
          </div>
          <div className="px-6 py-5">{children}</div>
          {footer && (
            <div className="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-800">
              {footer}
            </div>
          )}
        </div>
      )}
    </dialog>
  )
}
