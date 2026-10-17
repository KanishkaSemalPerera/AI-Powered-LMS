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
          { path: 'courses', lazy: page(() => import('@/pages/public/CoursesPage')) },
          { path: 'courses/:slug', lazy: page(() => import('@/pages/public/CourseDetailPage')) },
          { path: 'forbidden', lazy: page(() => import('@/pages/public/ForbiddenPage')) },
          { path: 'checkout/cancel', lazy: page(() => import('@/pages/public/CheckoutCancelPage')) },
          {
            element: <ProtectedRoute roles={[ROLES.STUDENT]} />,
            children: [{ path: 'checkout/success', lazy: page(() => import('@/pages/public/CheckoutSuccessPage')) }],
          },
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
          // Course player has its own full-screen layout. The API decides access
          // (enrolled students; owners and admins in preview mode).
          { path: 'learn/:slug', lazy: page(() => import('@/pages/student/CoursePlayerPage')) },
          { path: 'learn/:slug/lessons/:lessonId', lazy: page(() => import('@/pages/student/CoursePlayerPage')) },
          { path: 'learn/:slug/lessons/:lessonId/quiz', lazy: page(() => import('@/pages/student/QuizPage')) },
          {
            element: <DashboardLayout />,
            children: [
              { path: 'profile', lazy: page(() => import('@/pages/auth/ProfilePage')) },
              {
                element: <ProtectedRoute roles={[ROLES.STUDENT]} />,
                children: [
                  { path: 'dashboard', lazy: page(() => import('@/pages/student/StudentDashboardPage')) },
                  { path: 'my-courses', lazy: page(() => import('@/pages/student/MyCoursesPage')) },
                  { path: 'payments', lazy: page(() => import('@/pages/student/PaymentsPage')) },
                ],
              },
              {
                path: 'instructor',
                element: <ProtectedRoute roles={[ROLES.INSTRUCTOR]} />,
                children: [
                  { index: true, lazy: page(() => import('@/pages/instructor/InstructorDashboardPage')) },
                  { path: 'courses', lazy: page(() => import('@/pages/instructor/InstructorCoursesPage')) },
                  { path: 'courses/new', lazy: page(() => import('@/pages/instructor/CourseEditorPage')) },
                  { path: 'courses/:id/edit', lazy: page(() => import('@/pages/instructor/CourseEditorPage')) },
                  { path: 'courses/:courseId/lessons/:lessonId/quiz', lazy: page(() => import('@/pages/instructor/QuizEditorPage')) },
                ],
              },
              {
                path: 'admin',
                element: <ProtectedRoute roles={[ROLES.ADMIN]} />,
                children: [
                  { index: true, lazy: page(() => import('@/pages/admin/AdminDashboardPage')) },
                  { path: 'courses', lazy: page(() => import('@/pages/admin/AdminCourseApprovalsPage')) },
                  { path: 'categories', lazy: page(() => import('@/pages/admin/AdminCategoriesPage')) },
                ],
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
