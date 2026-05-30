import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchDashboard = async () => (await apiClient.get('/admin/installments/dashboard')).data
const fetchAnalytics = async () => (await apiClient.get('/admin/installments/analytics')).data

export default function AdminInstallmentDashboardPage() {
  const { data: dashboard } = useQuery({ queryKey: ['admin-installment-dashboard'], queryFn: fetchDashboard })
  const { data: analytics } = useQuery({ queryKey: ['admin-installment-analytics'], queryFn: fetchAnalytics })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Admin Installment Dashboard</h2>
      <div className="grid gap-3 md:grid-cols-3">
        <Card title="Plans" value={dashboard?.plans_total ?? 0} />
        <Card title="Contracts" value={dashboard?.contracts_total ?? 0} />
        <Card title="Defaulters" value={dashboard?.defaulters ?? 0} />
      </div>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Contract Status Analytics</h3>
        {analytics?.status_breakdown?.map((row) => <p key={row.status}>{row.status}: {row.total}</p>)}
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
