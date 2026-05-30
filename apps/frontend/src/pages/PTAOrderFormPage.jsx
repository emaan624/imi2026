import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/pta/services')).data
const placeOrder = async (payload) => (await apiClient.post('/pta/orders', payload)).data

export default function PTAOrderFormPage() {
  const { data: services = [] } = useQuery({ queryKey: ['pta-services-form'], queryFn: fetchServices })
  const [form, setForm] = useState({ pta_service_id: '', imei_1: '', imei_2: '', sim_type: 'single', registration_type: 'passport', passport_number: '', cnic_number: '' })
  const orderMutation = useMutation({ mutationFn: placeOrder })

  const submit = (event) => {
    event.preventDefault()
    orderMutation.mutate(form)
  }

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">PTA Order Form</h2>
      <form onSubmit={submit} className="grid gap-3 rounded border border-slate-800 bg-slate-900 p-4 md:grid-cols-2">
        <select className="rounded bg-slate-800 px-3 py-2" value={form.pta_service_id} onChange={(e) => setForm({ ...form, pta_service_id: e.target.value })} required>
          <option value="">Select Service</option>
          {services.map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="IMEI 1" value={form.imei_1} onChange={(e) => setForm({ ...form, imei_1: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="IMEI 2" value={form.imei_2} onChange={(e) => setForm({ ...form, imei_2: e.target.value })} />
        <select className="rounded bg-slate-800 px-3 py-2" value={form.sim_type} onChange={(e) => setForm({ ...form, sim_type: e.target.value })}>
          <option value="single">Single SIM</option>
          <option value="dual">Dual SIM</option>
        </select>
        <select className="rounded bg-slate-800 px-3 py-2" value={form.registration_type} onChange={(e) => setForm({ ...form, registration_type: e.target.value })}>
          <option value="passport">Passport</option>
          <option value="cnic">CNIC</option>
          <option value="overseas">Overseas Pakistani</option>
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Passport Number" value={form.passport_number} onChange={(e) => setForm({ ...form, passport_number: e.target.value })} />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="CNIC Number" value={form.cnic_number} onChange={(e) => setForm({ ...form, cnic_number: e.target.value })} />
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 font-medium hover:bg-cyan-500">Place Order</button>
      </form>
      {orderMutation.data && <p className="rounded border border-emerald-700 bg-emerald-900/40 p-3">Order placed: {orderMutation.data.order_number}</p>}
    </div>
  )
}
