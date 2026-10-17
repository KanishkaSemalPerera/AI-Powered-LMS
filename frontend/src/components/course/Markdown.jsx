import ReactMarkdown from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { cn } from '@/utils/cn'

const components = {
  // External links open in a new tab without giving the target page access to ours.
  a: ({ href, children, ...props }) => {
    const external = /^https?:\/\//.test(href ?? '')
    return (
      <a href={href} {...props} {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}>
        {children}
      </a>
    )
  },
}

/**
 * Renders lesson Markdown (GitHub-flavoured: tables, lists, code blocks).
 * Raw HTML in the source is NOT rendered, so instructor content can't inject scripts.
 */
export default function Markdown({ children, className }) {
  return (
    <div
      className={cn(
        'prose prose-slate max-w-none dark:prose-invert',
        'prose-headings:scroll-mt-24 prose-a:text-brand-600 dark:prose-a:text-brand-400',
        'prose-pre:bg-slate-900 prose-pre:text-slate-100 prose-code:before:content-none prose-code:after:content-none',
        'prose-code:rounded prose-code:bg-slate-100 prose-code:px-1 prose-code:py-0.5 prose-code:font-normal dark:prose-code:bg-slate-800',
        '[&_pre_code]:bg-transparent [&_pre_code]:p-0',
        className,
      )}
    >
      <ReactMarkdown remarkPlugins={[remarkGfm]} components={components}>
        {children ?? ''}
      </ReactMarkdown>
    </div>
  )
}
