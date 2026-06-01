import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchServices = async () => (await apiClient.get('/admin/imei/services')).data
const fetchPublicServices = async () => (await apiClient.get('/imei/services')).data
const createService = async (payload) => (await apiClient.post('/admin/imei/services', payload)).data
const updateService = async ({ id, payload }) => (await apiClient.put(`/admin/imei/services/${id}`, payload)).data

export default function ImeiServiceManagementPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ imei_category_id: '', name: '', slug: '', checker_type: 'fmi', price: '3.99', estimated_time_minutes: '5' })
  const { data: adminServices } = useQuery({ queryKey: ['admin-imei-services'], queryFn: fetchServices })
  const { data: categoriesData } = useQuery({ queryKey: ['imei-categories-for-admin'], queryFn: fetchPublicServices })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-imei-services'] })
    queryClient.invalidateQueries({ queryKey: ['imei-categories-for-admin'] })
  }

  const createMutation = useMutation({ mutationFn: createService, onSuccess: refresh })
  const toggleMutation = useMutation({ mutationFn: updateService, onSuccess: refresh })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Service Management</h2>
      <form
        className="grid gap-2 rounded border border-slate-800 bg-slate-900 p-4 md:grid-cols-3"
        onSubmit={(e) => {
          e.preventDefault()
          createMutation.mutate({
            ...form,
            imei_category_id: Number(form.imei_category_id),
            price: Number(form.price),
            estimated_time_minutes: Number(form.estimated_time_minutes),
            is_active: true,
          })
        }}
      >
        <select className="rounded bg-slate-800 px-3 py-2" value={form.imei_category_id} onChange={(e) => setForm({ ...form, imei_category_id: e.target.value })} required>
          <option value="">Category</option>
          {(categoriesData || []).map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Slug" value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} required />
        <select className="rounded bg-slate-800 px-3 py-2" value={form.checker_type} onChange={(e) => setForm({ ...form, checker_type: e.target.value })}>
          <option value="fmi">FMI Checker</option>
          <option value="carrier">Carrier Checker</option>
          <option value="blacklist">Blacklist Checker</option>
          <option value="warranty">Warranty Checker</option>
          <option value="network">Network Checker</option>
          <option value="device_info">Device Info Checker</option>
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Price" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="ETA minutes" value={form.estimated_time_minutes} onChange={(e) => setForm({ ...form, estimated_time_minutes: e.target.value })} required />
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500 md:col-span-3">Create Service</button>
      </form>
      <div className="space-y-2 text-sm">
        {adminServices?.data?.map((service) => (
          <div key={service.id} className="flex items-center justify-between rounded border border-slate-800 bg-slate-900 p-3">
            <p>{service.name} · {service.slug} · ${service.price} · {service.checker_type || 'provider api'} · {service.is_active ? 'active' : 'inactive'}</p>
            <button type="button" onClick={() => toggleMutation.mutate({ id: service.id, payload: { is_active: !service.is_active } })} className="rounded bg-slate-700 px-2 py-1">{service.is_active ? 'Deactivate' : 'Activate'}</button>
          </div>
        ))}
      </div>
    </div>
  )
}
