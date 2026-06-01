import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchAnalytics = async () => (await apiClient.get('/admin/imei/analytics')).data
const fetchOrders = async () => (await apiClient.get('/admin/imei/orders')).data
const markRefund = async ({ id, amount }) => (await apiClient.post(`/admin/imei/orders/${id}/refund`, { amount })).data

export default function ImeiAnalyticsDashboardPage() {
  const queryClient = useQueryClient()
  const { data } = useQuery({ queryKey: ['admin-imei-analytics'], queryFn: fetchAnalytics })
  const { data: orders } = useQuery({ queryKey: ['admin-imei-orders'], queryFn: fetchOrders })
  const refundMutation = useMutation({
    mutationFn: markRefund,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-imei-analytics'] })
      queryClient.invalidateQueries({ queryKey: ['admin-imei-orders'] })
    },
  })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">IMEI Analytics Dashboard</h2>
      <div className="grid gap-3 md:grid-cols-3">
        <Card title="Profit" value={data?.profit_analytics?.gross_profit ?? 0} />
        <Card title="Revenue" value={data?.revenue_analytics?.total_revenue ?? 0} />
        <Card title="Failed Orders" value={data?.failed_order_management?.failed_orders ?? 0} />
      </div>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">API Statistics</h3>
        <p>Providers: {data?.api_statistics?.providers_total ?? 0}</p>
        <p>Services: {data?.api_statistics?.services_total ?? 0}</p>
        <p>Orders: {data?.api_statistics?.orders_total ?? 0}</p>
        <p>Bulk Orders: {data?.api_statistics?.bulk_orders_total ?? 0}</p>
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Provider Balance Dashboard</h3>
        {data?.provider_balance_dashboard?.map((item) => (
          <p key={item.id}>{item.provider?.name}: {item.balance} {item.currency}</p>
        ))}
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Failed Order & Refund Management</h3>
        {orders?.data?.map((order) => (
          <div key={order.id} className="mb-2 flex items-center justify-between rounded border border-slate-800 p-2">
            <p>{order.order_number} · {order.status} · ${order.price}</p>
            {order.status === 'failed' && (
              <button type="button" onClick={() => refundMutation.mutate({ id: order.id, amount: order.price })} className="rounded bg-amber-600 px-2 py-1 hover:bg-amber-500">Mark Refund</button>
            )}
          </div>
        ))}
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
