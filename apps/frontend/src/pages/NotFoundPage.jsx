import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return (
    <div className="mx-auto mt-24 max-w-lg rounded border border-slate-800 bg-slate-900 p-6 text-center">
      <h2 className="text-2xl font-semibold">Page not found</h2>
      <p className="mt-2 text-slate-400">The requested page does not exist.</p>
      <Link to="/dashboard" className="mt-4 inline-block rounded bg-cyan-600 px-4 py-2">Go dashboard</Link>
    </div>
  )
}
