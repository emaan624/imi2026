import { useMutation, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/imei/services')).data
const submitBulk = async (payload) => (await apiClient.post('/imei/bulk-orders', payload)).data

export default function ImeiBulkUploadPage() {
  const [form, setForm] = useState({ imei_service_id: '', csv: '' })
  const { data } = useQuery({ queryKey: ['imei-services-for-bulk'], queryFn: fetchServices })
  const mutation = useMutation({ mutationFn: submitBulk })

  const services = (data ?? []).flatMap((category) => category.services || [])

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Bulk CSV Upload</h2>
      <form
        className="space-y-3 rounded border border-slate-800 bg-slate-900 p-4"
        onSubmit={(e) => {
          e.preventDefault()
          mutation.mutate({ imei_service_id: Number(form.imei_service_id), csv: form.csv })
        }}
      >
        <select className="w-full rounded bg-slate-800 px-3 py-2" value={form.imei_service_id} onChange={(e) => setForm({ ...form, imei_service_id: e.target.value })} required>
          <option value="">Select Service</option>
          {services.map((service) => <option key={service.id} value={service.id}>{service.name}</option>)}
        </select>
        <textarea className="h-40 w-full rounded bg-slate-800 px-3 py-2" placeholder="One IMEI per line or CSV first column" value={form.csv} onChange={(e) => setForm({ ...form, csv: e.target.value })} required />
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Create Bulk Order</button>
      </form>
      {mutation.data && (
        <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
          <p>Bulk Number: {mutation.data.bulk_number}</p>
          <p>Total Orders: {mutation.data.total_orders}</p>
          <p>Status: {mutation.data.status}</p>
        </section>
      )}
    </div>
  )
}
