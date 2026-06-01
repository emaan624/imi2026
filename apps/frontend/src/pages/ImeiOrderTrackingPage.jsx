import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchOrders = async () => (await apiClient.get('/imei/orders')).data
const fetchBulkOrders = async () => (await apiClient.get('/imei/bulk-orders')).data

export default function ImeiOrderTrackingPage() {
  const { data: orders } = useQuery({ queryKey: ['imei-orders'], queryFn: fetchOrders })
  const { data: bulks } = useQuery({ queryKey: ['imei-bulk-orders'], queryFn: fetchBulkOrders })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Order Tracking</h2>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Single Orders</h3>
        <div className="space-y-2">
          {orders?.data?.map((order) => (
            <p key={order.id}>{order.order_number} · {order.imei} · {order.status} · {order.service?.name}</p>
          ))}
        </div>
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Bulk Orders</h3>
        <div className="space-y-2">
          {bulks?.data?.map((bulk) => (
            <p key={bulk.id}>{bulk.bulk_number} · {bulk.status} · {bulk.processed_orders}/{bulk.total_orders}</p>
          ))}
        </div>
      </section>
    </div>
  )
}
