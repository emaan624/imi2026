import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/imei/services')).data
const placeOrder = async (payload) => (await apiClient.post('/imei/orders', payload)).data

export default function ImeiPlaceOrderPage() {
  const [form, setForm] = useState({ imei_service_id: '', imei: '' })
  const { data } = useQuery({ queryKey: ['imei-services-for-order'], queryFn: fetchServices })
  const mutation = useMutation({ mutationFn: placeOrder })

  const services = (data ?? []).flatMap((category) => category.services || [])

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Place IMEI Order</h2>
      <form
        className="grid gap-3 rounded border border-slate-800 bg-slate-900 p-4 md:grid-cols-3"
        onSubmit={(e) => {
          e.preventDefault()
          mutation.mutate({ imei_service_id: Number(form.imei_service_id), imei: form.imei })
        }}
      >
        <select className="rounded bg-slate-800 px-3 py-2" value={form.imei_service_id} onChange={(e) => setForm({ ...form, imei_service_id: e.target.value })} required>
          <option value="">Select Service</option>
          {services.map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="15-digit IMEI" value={form.imei} onChange={(e) => setForm({ ...form, imei: e.target.value })} required />
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Submit Order</button>
      </form>
      {mutation.data && (
        <section className="rounded border border-emerald-800 bg-emerald-900/20 p-4 text-sm">
          <p className="font-medium">Order created: {mutation.data.order_number}</p>
          <p>Status: {mutation.data.status}</p>
        </section>
      )}
    </div>
  )
}
