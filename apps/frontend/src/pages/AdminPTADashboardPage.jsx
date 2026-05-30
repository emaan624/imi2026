import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchDashboard = async () => (await apiClient.get('/admin/pta/dashboard')).data
const fetchRevenue = async () => (await apiClient.get('/admin/pta/revenue-report')).data

export default function AdminPTADashboardPage() {
  const { data: dashboard } = useQuery({ queryKey: ['admin-pta-dashboard'], queryFn: fetchDashboard })
  const { data: revenue } = useQuery({ queryKey: ['admin-pta-revenue'], queryFn: fetchRevenue })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Admin PTA Dashboard</h2>
      <div className="grid gap-3 md:grid-cols-3">
        <Card title="Services" value={dashboard?.services_total ?? 0} />
        <Card title="Orders" value={dashboard?.orders_total ?? 0} />
        <Card title="Revenue" value={dashboard?.pta_revenue ?? 0} />
      </div>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Revenue Report (Last 30 Days)</h3>
        {revenue?.daily?.map((row) => <p key={row.date}>{row.date}: {row.total}</p>)}
      </section>
    </div>
  )
}

function Card({ title, value }) {
  return (
    <section className="rounded border border-slate-800 bg-slate-900 p-4">
      <h3 className="text-sm text-slate-400">{title}</h3>
      <p className="text-2xl font-bold">{value}</p>
    </section>
  )
}
