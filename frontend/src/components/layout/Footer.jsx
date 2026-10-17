const APP_NAME = import.meta.env.VITE_APP_NAME || 'LearnHub'
const YEAR = new Date().getFullYear()

export default function Footer() {
  return (
    <footer className="border-t border-slate-200 dark:border-slate-800">
      <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:px-6 lg:px-8 dark:text-slate-400">
        <p>
          © {YEAR} {APP_NAME}. All rights reserved.
        </p>
        <p>Learn smarter with AI-powered courses.</p>
      </div>
    </footer>
  )
}
