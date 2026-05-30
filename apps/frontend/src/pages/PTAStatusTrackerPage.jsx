import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchOrders = async () => (await apiClient.get('/pta/orders')).data

export default function PTAStatusTrackerPage() {
  const { data } = useQuery({ queryKey: ['pta-orders'], queryFn: fetchOrders })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">PTA Status Tracker</h2>
      <div className="space-y-2">
        {data?.data?.map((order) => (
          <div key={order.id} className="rounded border border-slate-800 bg-slate-900 p-3 text-sm">
            <p className="font-medium">{order.order_number}</p>
            <p>Status: <span className="uppercase text-cyan-300">{order.status}</span></p>
            <p>Paid/Total: {order.paid_amount} / {order.total_amount}</p>
          </div>
        ))}
      </div>
    </div>
  )
}
