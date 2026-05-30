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
            <Link to="/pta/calculator" className="hover:text-cyan-400">PTA Calculator</Link>
            <Link to="/pta/services" className="hover:text-cyan-400">PTA Services</Link>
            <Link to="/pta/order" className="hover:text-cyan-400">PTA Order</Link>
            <Link to="/pta/status" className="hover:text-cyan-400">PTA Status</Link>
            <Link to="/installments/plans" className="hover:text-cyan-400">Plans</Link>
            <Link to="/installments" className="hover:text-cyan-400">My Installments</Link>
            {user?.role === 'admin' && <Link to="/admin" className="hover:text-cyan-400">Admin Dashboard</Link>}
            {user?.role === 'admin' && <Link to="/admin/pta" className="hover:text-cyan-400">Admin PTA</Link>}
            {user?.role === 'admin' && <Link to="/admin/installments" className="hover:text-cyan-400">Admin Installments</Link>}
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
