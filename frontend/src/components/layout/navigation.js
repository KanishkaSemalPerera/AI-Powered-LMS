import {
  BookOpen,
  ClipboardCheck,
  Compass,
  FolderTree,
  LayoutDashboard,
  Library,
  Receipt,
  ShieldCheck,
  Sparkles,
  UserRound,
} from 'lucide-react'
import { ROLES } from '@/utils/roles'

/** Sidebar links for each role's signed-in area. Later phases append to these lists. */
export const sidebarNavigation = {
  [ROLES.STUDENT]: [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/my-courses', label: 'My courses', icon: BookOpen },
    { to: '/courses', label: 'Browse courses', icon: Compass },
    { to: '/payments', label: 'Payments', icon: Receipt },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.INSTRUCTOR]: [
    { to: '/instructor', label: 'Dashboard', icon: LayoutDashboard, end: true },
    { to: '/instructor/courses', label: 'My courses', icon: Library },
    { to: '/instructor/ai/quiz-generator', label: 'AI quiz generator', icon: Sparkles },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
  [ROLES.ADMIN]: [
    { to: '/admin', label: 'Dashboard', icon: ShieldCheck, end: true },
    { to: '/admin/courses', label: 'Course approvals', icon: ClipboardCheck },
    { to: '/admin/categories', label: 'Categories', icon: FolderTree },
    { to: '/profile', label: 'Profile', icon: UserRound },
  ],
}
