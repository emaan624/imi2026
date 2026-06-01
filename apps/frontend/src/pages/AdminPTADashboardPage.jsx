import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchDashboard = async () => (await apiClient.get('/admin/pta/dashboard')).data
const fetchRevenue = async () => (await apiClient.get('/admin/pta/revenue-report')).data
const fetchServices = async () => (await apiClient.get('/admin/pta/services')).data
const fetchOrders = async () => (await apiClient.get('/admin/pta/orders')).data
const createService = async (payload) => (await apiClient.post('/admin/pta/services', payload)).data
const updateService = async ({ id, payload }) => (await apiClient.put(`/admin/pta/services/${id}`, payload)).data
const deleteService = async (id) => (await apiClient.delete(`/admin/pta/services/${id}`)).data
const updateOrderStatus = async ({ id, status, notes }) => (await apiClient.post(`/admin/pta/orders/${id}/status`, { status, notes })).data

export default function AdminPTADashboardPage() {
  const queryClient = useQueryClient()
  const [serviceForm, setServiceForm] = useState({ name: '', slug: '', base_fee: '500' })
  const [statusForm, setStatusForm] = useState({ id: '', status: 'in_review', notes: '' })
  const { data: dashboard } = useQuery({ queryKey: ['admin-pta-dashboard'], queryFn: fetchDashboard })
  const { data: revenue } = useQuery({ queryKey: ['admin-pta-revenue'], queryFn: fetchRevenue })
  const { data: services } = useQuery({ queryKey: ['admin-pta-services'], queryFn: fetchServices })
  const { data: orders } = useQuery({ queryKey: ['admin-pta-orders'], queryFn: fetchOrders })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-pta-dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['admin-pta-services'] })
    queryClient.invalidateQueries({ queryKey: ['admin-pta-orders'] })
  }

  const createMutation = useMutation({ mutationFn: createService, onSuccess: refresh })
  const statusMutation = useMutation({ mutationFn: updateOrderStatus, onSuccess: refresh })
  const toggleMutation = useMutation({ mutationFn: updateService, onSuccess: refresh })
  const deleteMutation = useMutation({ mutationFn: deleteService, onSuccess: refresh })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Admin PTA Dashboard</h2>
      <div className="grid gap-3 md:grid-cols-3">
        <Card title="Services" value={dashboard?.services_total ?? 0} />
        <Card title="Orders" value={dashboard?.orders_total ?? 0} />
        <Card title="Revenue" value={dashboard?.pta_revenue ?? 0} />
      </div>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Revenue Report (Last 30 Days)</h3>
        {revenue?.daily?.map((row) => <p key={row.date}>{row.date}: {row.total}</p>)}
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Service Management</h3>
        <form
          className="mb-3 grid gap-2 md:grid-cols-4"
          onSubmit={(e) => {
            e.preventDefault()
            createMutation.mutate({ ...serviceForm, base_fee: Number(serviceForm.base_fee), is_active: true })
          }}
        >
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Name" value={serviceForm.name} onChange={(e) => setServiceForm({ ...serviceForm, name: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Slug" value={serviceForm.slug} onChange={(e) => setServiceForm({ ...serviceForm, slug: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Base fee" value={serviceForm.base_fee} onChange={(e) => setServiceForm({ ...serviceForm, base_fee: e.target.value })} required />
          <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Create</button>
        </form>
        <div className="space-y-2">
          {services?.data?.map((service) => (
            <div key={service.id} className="flex items-center justify-between rounded border border-slate-800 p-2">
              <p>{service.name} ({service.base_fee})</p>
              <div className="flex gap-2">
                <button type="button" onClick={() => toggleMutation.mutate({ id: service.id, payload: { is_active: !service.is_active } })} className="rounded bg-slate-700 px-2 py-1">{service.is_active ? 'Deactivate' : 'Activate'}</button>
                <button type="button" onClick={() => deleteMutation.mutate(service.id)} className="rounded bg-rose-700 px-2 py-1">Delete</button>
              </div>
            </div>
          ))}
        </div>
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Order Status Updates</h3>
        <form
          className="mb-3 grid gap-2 md:grid-cols-4"
          onSubmit={(e) => {
            e.preventDefault()
            statusMutation.mutate({ id: Number(statusForm.id), status: statusForm.status, notes: statusForm.notes })
          }}
        >
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Order ID" value={statusForm.id} onChange={(e) => setStatusForm({ ...statusForm, id: e.target.value })} required />
          <select className="rounded bg-slate-800 px-3 py-2" value={statusForm.status} onChange={(e) => setStatusForm({ ...statusForm, status: e.target.value })}>
            <option value="in_review">In Review</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Notes" value={statusForm.notes} onChange={(e) => setStatusForm({ ...statusForm, notes: e.target.value })} />
          <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Update</button>
        </form>
        <div className="space-y-2">
          {orders?.data?.map((order) => <p key={order.id}>{order.id} · {order.order_number} · {order.status} · {order.user?.email}</p>)}
        </div>
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
