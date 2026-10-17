import Spinner from './Spinner'

/** Centered spinner for route transitions and session restore. */
export default function PageLoader({ fullScreen = false, label = 'Loading page' }) {
  return (
    <div className={fullScreen ? 'grid min-h-screen place-items-center' : 'grid min-h-[50vh] place-items-center'}>
      <Spinner className="size-8" label={label} />
    </div>
  )
}
