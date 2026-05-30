import { Link, Outlet } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export default function AppLayout() {
  const { user, logout } = useAuth()

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800 bg-slate-900/80">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
          <h1 className="font-semibold">IMI Platform</h1>
          <nav className="flex items-center gap-4 text-sm">
            <Link to="/dashboard" className="hover:text-cyan-400">User Dashboard</Link>
            {user?.role === 'admin' && <Link to="/admin" className="hover:text-cyan-400">Admin Dashboard</Link>}
            <button onClick={logout} className="rounded bg-slate-700 px-3 py-1 hover:bg-slate-600">Logout</button>
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-6xl p-4">
        <Outlet />
      </main>
    </div>
  )
}
