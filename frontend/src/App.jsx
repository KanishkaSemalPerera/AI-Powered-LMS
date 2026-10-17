import { RouterProvider } from 'react-router'
import { Toaster } from 'sonner'
import { useTheme } from '@/hooks/useTheme'
import { router } from '@/routes/router'

export default function App() {
  const { resolvedTheme } = useTheme()

  return (
    <>
      <RouterProvider router={router} />
      <Toaster theme={resolvedTheme} position="top-right" richColors closeButton />
    </>
  )
}
