import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchAdminStats = async () => (await apiClient.get('/admin/dashboard')).data
const fetchUsers = async () => (await apiClient.get('/admin/users')).data

export default function AdminDashboardPage() {
  const { data: stats } = useQuery({ queryKey: ['admin-stats'], queryFn: fetchAdminStats })
  const { data: users } = useQuery({ queryKey: ['admin-users'], queryFn: fetchUsers })

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-semibold">Admin Dashboard</h2>
      <div className="grid gap-4 md:grid-cols-4">
        <Card title="Users" value={stats?.users_total ?? 0} />
        <Card title="Wallet Volume" value={stats?.wallet_volume ?? 0} />
        <Card title="Open Tickets" value={stats?.tickets_open ?? 0} />
        <Card title="Today Transactions" value={stats?.transactions_today ?? 0} />
      </div>

      <section className="rounded border border-slate-800 bg-slate-900 p-4">
        <h3 className="mb-3 font-medium">Users</h3>
        <div className="space-y-2 text-sm">
          {users?.data?.map((user) => (
            <div key={user.id} className="flex justify-between rounded bg-slate-800 px-3 py-2">
              <span>{user.name} ({user.email})</span>
              <span className="uppercase text-cyan-300">{user.role}</span>
            </div>
          ))}
        </div>
      </section>
    </div>
  )
}

function Card({ title, value }) {
  return (
    <section className="rounded border border-slate-800 bg-slate-900 p-4">
      <h3 className="mb-2 text-sm text-slate-400">{title}</h3>
      <p className="text-2xl font-bold">{value}</p>
    </section>
  )
}
