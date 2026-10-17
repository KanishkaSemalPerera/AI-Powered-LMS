import { createBrowserRouter } from 'react-router'
import MainLayout from '@/layouts/MainLayout'
import HomePage from '@/pages/public/HomePage'
import NotFoundPage from '@/pages/public/NotFoundPage'
import RouteErrorPage from '@/pages/public/RouteErrorPage'

/**
 * Application routes. Auth, student, instructor and admin route groups
 * (with role guards) are added in later phases.
 */
export const router = createBrowserRouter([
  {
    element: <MainLayout />,
    errorElement: <RouteErrorPage />,
    children: [
      { index: true, element: <HomePage /> },
      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
