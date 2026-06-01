import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchOrders = async () => (await apiClient.get('/pta/orders')).data
const fetchOrder = async (id) => (await apiClient.get(`/pta/orders/${id}`)).data
const fetchStatus = async (id) => (await apiClient.get(`/pta/orders/${id}/status`)).data
const fetchHistory = async (id) => (await apiClient.get(`/pta/orders/${id}/history`)).data
const fetchInvoice = async (id) => (await apiClient.get(`/pta/orders/${id}/invoice`)).data
const fetchReceipt = async (id) => (await apiClient.get(`/pta/orders/${id}/receipt`)).data
const payOrder = async ({ id, amount }) => (await apiClient.post(`/pta/orders/${id}/pay`, { amount })).data
const updateRegistration = async ({ id, type, value }) => (await apiClient.post(`/pta/orders/${id}/register/${type}`, type === 'cnic' ? { cnic_number: value } : { passport_number: value })).data

export default function PTAStatusTrackerPage() {
  const queryClient = useQueryClient()
  const [selectedOrderId, setSelectedOrderId] = useState(null)
  const [paymentAmount, setPaymentAmount] = useState('1000')
  const [registrationValue, setRegistrationValue] = useState('')
  const [registrationType, setRegistrationType] = useState('passport')
  const { data } = useQuery({ queryKey: ['pta-orders'], queryFn: fetchOrders })
  const { data: order } = useQuery({ queryKey: ['pta-order', selectedOrderId], queryFn: () => fetchOrder(selectedOrderId), enabled: Boolean(selectedOrderId) })
  const { data: status } = useQuery({ queryKey: ['pta-order-status', selectedOrderId], queryFn: () => fetchStatus(selectedOrderId), enabled: Boolean(selectedOrderId) })
  const { data: history = [] } = useQuery({ queryKey: ['pta-order-history', selectedOrderId], queryFn: () => fetchHistory(selectedOrderId), enabled: Boolean(selectedOrderId) })
  const { data: invoice } = useQuery({ queryKey: ['pta-order-invoice', selectedOrderId], queryFn: () => fetchInvoice(selectedOrderId), enabled: Boolean(selectedOrderId) })
  const { data: receipt } = useQuery({ queryKey: ['pta-order-receipt', selectedOrderId], queryFn: () => fetchReceipt(selectedOrderId), enabled: Boolean(selectedOrderId) })

  const refreshOrder = () => {
    queryClient.invalidateQueries({ queryKey: ['pta-orders'] })
    queryClient.invalidateQueries({ queryKey: ['pta-order', selectedOrderId] })
    queryClient.invalidateQueries({ queryKey: ['pta-order-status', selectedOrderId] })
    queryClient.invalidateQueries({ queryKey: ['pta-order-history', selectedOrderId] })
    queryClient.invalidateQueries({ queryKey: ['pta-order-receipt', selectedOrderId] })
  }

  const paymentMutation = useMutation({
    mutationFn: payOrder,
    onSuccess: refreshOrder,
  })
  const registrationMutation = useMutation({
    mutationFn: updateRegistration,
    onSuccess: refreshOrder,
  })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">PTA Status Tracker</h2>
      <div className="space-y-2">
        {data?.data?.map((order) => (
          <button type="button" onClick={() => setSelectedOrderId(order.id)} key={order.id} className="block w-full rounded border border-slate-800 bg-slate-900 p-3 text-left text-sm hover:border-cyan-700">
            <p className="font-medium">{order.order_number}</p>
            <p>Status: <span className="uppercase text-cyan-300">{order.status}</span></p>
            <p>Paid/Total: {order.paid_amount} / {order.total_amount}</p>
          </button>
        ))}
      </div>
      {selectedOrderId && (
        <div className="grid gap-4 rounded border border-slate-800 bg-slate-900 p-4 text-sm md:grid-cols-2">
          <section className="space-y-2">
            <h3 className="font-medium">Selected Order</h3>
            <p>{status?.order_number}</p>
            <p>Status: {status?.status}</p>
            <p>Approved At: {status?.approved_at || 'N/A'}</p>
            <p>Total: {status?.total_amount}</p>
            <p>Paid: {status?.paid_amount}</p>
            <div className="flex gap-2">
              <input className="w-full rounded bg-slate-800 px-3 py-2" value={paymentAmount} onChange={(e) => setPaymentAmount(e.target.value)} />
              <button type="button" onClick={() => paymentMutation.mutate({ id: selectedOrderId, amount: Number(paymentAmount) })} className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Pay</button>
            </div>
            <div className="flex gap-2">
              <select className="rounded bg-slate-800 px-3 py-2" value={registrationType} onChange={(e) => setRegistrationType(e.target.value)}>
                <option value="passport">Passport</option>
                <option value="cnic">CNIC</option>
                <option value="overseas">Overseas</option>
              </select>
              <input className="w-full rounded bg-slate-800 px-3 py-2" placeholder="Registration number" value={registrationValue} onChange={(e) => setRegistrationValue(e.target.value)} />
              <button type="button" onClick={() => registrationMutation.mutate({ id: selectedOrderId, type: registrationType, value: registrationValue })} className="rounded bg-slate-700 px-3 py-2 hover:bg-slate-600">Update</button>
            </div>
          </section>
          <section className="space-y-2">
            <h3 className="font-medium">Invoice / Receipt</h3>
            <p>Invoice: {invoice?.invoice_no}</p>
            <p>Receipt: {receipt?.receipt_no}</p>
            <p>Wallet Paid: {receipt?.wallet_payment_amount}</p>
            <h4 className="pt-2 font-medium">History</h4>
            {history.map((entry) => (
              <p key={entry.id}>{entry.action} ({entry.from_status || '-'} → {entry.to_status || '-'})</p>
            ))}
          </section>
          {order?.logs?.length > 0 && (
            <section className="space-y-2 md:col-span-2">
              <h4 className="font-medium">Audit Trail</h4>
              {order.logs.map((entry) => (
                <p key={entry.id}>{entry.action}: {entry.notes || 'No notes'}</p>
              ))}
            </section>
          )}
        </div>
      )}
    </div>
  )
}
