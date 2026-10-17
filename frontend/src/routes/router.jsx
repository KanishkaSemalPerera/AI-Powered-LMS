import { createBrowserRouter } from 'react-router'
import AuthLayout from '@/layouts/AuthLayout'
import DashboardLayout from '@/layouts/DashboardLayout'
import MainLayout from '@/layouts/MainLayout'
import HomePage from '@/pages/public/HomePage'
import NotFoundPage from '@/pages/public/NotFoundPage'
import RouteErrorPage from '@/pages/public/RouteErrorPage'
import { ROLES } from '@/utils/roles'
import GuestRoute from './GuestRoute'
import ProtectedRoute from './ProtectedRoute'

/**
 * Code-split page: the module is downloaded the first time the route is visited.
 * Usage: { path: 'login', lazy: page(() => import('@/pages/auth/LoginPage')) }
 */
const page = (loader) => async () => ({ Component: (await loader()).default })

export const router = createBrowserRouter([
  {
    errorElement: <RouteErrorPage />,
    children: [
      // ---------------------------------------------------------- Public site
      {
        element: <MainLayout />,
        children: [
          { index: true, element: <HomePage /> },
          { path: 'forbidden', lazy: page(() => import('@/pages/public/ForbiddenPage')) },
        ],
      },

      // ---------------------------------------------------------- Guests only
      {
        element: <GuestRoute />,
        children: [
          {
            element: <AuthLayout />,
            children: [
              { path: 'login', lazy: page(() => import('@/pages/auth/LoginPage')) },
              { path: 'register', lazy: page(() => import('@/pages/auth/RegisterPage')) },
              { path: 'forgot-password', lazy: page(() => import('@/pages/auth/ForgotPasswordPage')) },
              { path: 'reset-password', lazy: page(() => import('@/pages/auth/ResetPasswordPage')) },
            ],
          },
        ],
      },

      // ---------------------------------------------------------- Signed in
      {
        element: <ProtectedRoute />,
        children: [
          {
            element: <DashboardLayout />,
            children: [
              { path: 'profile', lazy: page(() => import('@/pages/auth/ProfilePage')) },
              {
                element: <ProtectedRoute roles={[ROLES.STUDENT]} />,
                children: [{ path: 'dashboard', lazy: page(() => import('@/pages/student/StudentDashboardPage')) }],
              },
              {
                path: 'instructor',
                element: <ProtectedRoute roles={[ROLES.INSTRUCTOR]} />,
                children: [{ index: true, lazy: page(() => import('@/pages/instructor/InstructorDashboardPage')) }],
              },
              {
                path: 'admin',
                element: <ProtectedRoute roles={[ROLES.ADMIN]} />,
                children: [{ index: true, lazy: page(() => import('@/pages/admin/AdminDashboardPage')) }],
              },
            ],
          },
        ],
      },

      // ---------------------------------------------------------- 404
      {
        element: <MainLayout />,
        children: [{ path: '*', element: <NotFoundPage /> }],
      },
    ],
  },
])
