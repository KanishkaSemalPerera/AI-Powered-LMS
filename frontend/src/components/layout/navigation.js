import { LayoutDashboard, ShieldCheck, UserRound } from 'lucide-react'
import { ROLES } from '@/utils/roles'

/** Sidebar links for each role's signed-in area. Later phases append to these lists. */
export const sidebarNavigation = {
  [ROLES.STUDENT]: [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.INSTRUCTOR]: [
    { to: '/instructor', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.ADMIN]: [
    { to: '/admin', label: 'Dashboard', icon: ShieldCheck, end: true },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
}
