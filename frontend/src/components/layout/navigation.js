import { ClipboardCheck, Compass, FolderTree, LayoutDashboard, Library, ShieldCheck, UserRound } from 'lucide-react'
import { ROLES } from '@/utils/roles'

/** Sidebar links for each role's signed-in area. Later phases append to these lists. */
export const sidebarNavigation = {
  [ROLES.STUDENT]: [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/courses', label: 'Browse courses', icon: Compass },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.INSTRUCTOR]: [
    { to: '/instructor', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/instructor/courses', label: 'My courses', icon: Library },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.ADMIN]: [
    { to: '/admin', label: 'Dashboard', icon: ShieldCheck, end: true },
    { to: '/admin/courses', label: 'Course approvals', icon: ClipboardCheck },
    { to: '/admin/categories', label: 'Categories', icon: FolderTree },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
}
