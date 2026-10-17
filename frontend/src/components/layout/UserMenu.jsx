import { ChevronDown, LayoutDashboard, LogOut, UserRound } from 'lucide-react'
import { useEffect, useId, useRef, useState } from 'react'
import { Link } from 'react-router'
import { toast } from 'sonner'
import Avatar from '@/components/ui/Avatar'
import Badge from '@/components/ui/Badge'
import { useAuth } from '@/hooks/useAuth'
import { dashboardPath } from '@/utils/roles'

const itemClass =
  'flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 focus-visible:bg-slate-100 ' +
  'dark:text-slate-200 dark:hover:bg-slate-800 dark:focus-visible:bg-slate-800'

/** Avatar button with a dropdown: dashboard, profile, log out. Closes on outside click and Escape. */
export default function UserMenu() {
  const { user, logout } = useAuth()
  const [open, setOpen] = useState(false)
  const containerRef = useRef(null)
  const buttonRef = useRef(null)
  const menuId = useId()

  useEffect(() => {
    if (!open) return undefined

    const onPointerDown = (event) => {
      if (!containerRef.current?.contains(event.target)) setOpen(false)
    }
    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        setOpen(false)
        buttonRef.current?.focus()
      }
    }

    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open])

  // On protected pages the route guard then sends the user home; on public pages they stay put.
  const handleLogout = async () => {
    setOpen(false)
    await logout()
    toast.success('You have been logged out.')
  }

  return (
    <div ref={containerRef} className="relative">
      <button
        ref={buttonRef}
        type="button"
        onClick={() => setOpen((value) => !value)}
        aria-expanded={open}
        aria-haspopup="menu"
        aria-controls={menuId}
        className="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-slate-100 dark:hover:bg-slate-800"
      >
        <Avatar src={user.avatar_url} name={user.name} size="sm" />
        <span className="hidden max-w-32 truncate text-sm font-medium sm:block">{user.name}</span>
        <ChevronDown className="size-4 text-slate-500" aria-hidden="true" />
        <span className="sr-only">Open account menu</span>
      </button>

      {open && (
        <div
          id={menuId}
          role="menu"
          className="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-800 dark:bg-slate-900"
        >
          <div className="border-b border-slate-200 px-3 pb-3 pt-1 dark:border-slate-800">
            <p className="truncate text-sm font-semibold">{user.name}</p>
            <p className="truncate text-xs text-slate-500 dark:text-slate-400">{user.email}</p>
            <Badge variant="brand" className="mt-2">
              {user.role_label}
            </Badge>
          </div>
          <div className="pt-2">
            <Link role="menuitem" to={dashboardPath(user.role)} className={itemClass} onClick={() => setOpen(false)}>
              <LayoutDashboard className="size-4" aria-hidden="true" />
              Dashboard
            </Link>
            <Link role="menuitem" to="/profile" className={itemClass} onClick={() => setOpen(false)}>
              <UserRound className="size-4" aria-hidden="true" />
              Profile
            </Link>
            <button role="menuitem" type="button" className={itemClass} onClick={handleLogout}>
              <LogOut className="size-4" aria-hidden="true" />
              Log out
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
