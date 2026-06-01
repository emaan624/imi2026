import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchProviders = async () => (await apiClient.get('/admin/imei/providers')).data
const createProvider = async (payload) => (await apiClient.post('/admin/imei/providers', payload)).data
const updateProvider = async ({ id, payload }) => (await apiClient.put(`/admin/imei/providers/${id}`, payload)).data

export default function ImeiProviderManagementPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ name: '', slug: '', type: 'generic_rest', priority: '10', api_url: '' })
  const { data } = useQuery({ queryKey: ['admin-imei-providers'], queryFn: fetchProviders })

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin-imei-providers'] })
  const createMutation = useMutation({ mutationFn: createProvider, onSuccess: refresh })
  const updateMutation = useMutation({ mutationFn: updateProvider, onSuccess: refresh })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Provider Management</h2>
      <form
        className="grid gap-2 rounded border border-slate-800 bg-slate-900 p-4 md:grid-cols-5"
        onSubmit={(e) => {
          e.preventDefault()
          createMutation.mutate({ ...form, priority: Number(form.priority), is_active: true })
        }}
      >
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Slug" value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} required />
        <select className="rounded bg-slate-800 px-3 py-2" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
          <option value="unlockbase">UnlockBase</option>
          <option value="dhru_fusion">Dhru Fusion</option>
          <option value="generic_rest">Generic REST</option>
          <option value="generic_xml">Generic XML</option>
        </select>
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="Priority" value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="API URL" value={form.api_url} onChange={(e) => setForm({ ...form, api_url: e.target.value })} />
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500 md:col-span-5">Add Provider</button>
      </form>
      <div className="space-y-2">
        {data?.data?.map((provider) => (
          <div key={provider.id} className="flex items-center justify-between rounded border border-slate-800 bg-slate-900 p-3 text-sm">
            <p>{provider.name} · {provider.type} · priority {provider.priority} · {provider.is_active ? 'active' : 'inactive'}</p>
            <button type="button" onClick={() => updateMutation.mutate({ id: provider.id, payload: { is_active: !provider.is_active } })} className="rounded bg-slate-700 px-2 py-1">{provider.is_active ? 'Disable' : 'Enable'}</button>
          </div>
        ))}
      </div>
    </div>
  )
}
