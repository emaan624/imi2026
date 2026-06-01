import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const fetchDashboard = async () => (await apiClient.get('/admin/installments/dashboard')).data
const fetchAnalytics = async () => (await apiClient.get('/admin/installments/analytics')).data
const fetchPlans = async () => (await apiClient.get('/admin/installments/plans')).data
const fetchContracts = async () => (await apiClient.get('/admin/installments/contracts')).data
const createPlan = async (payload) => (await apiClient.post('/admin/installments/plans', payload)).data
const approveContract = async (id) => (await apiClient.post(`/admin/installments/contracts/${id}/approve`, { verification_status: 'verified' })).data
const markDefaulter = async ({ id, reason }) => (await apiClient.post(`/admin/installments/contracts/${id}/mark-defaulter`, { reason })).data
const addRecovery = async ({ id, note }) => (await apiClient.post(`/admin/installments/contracts/${id}/recovery-note`, { note })).data
const adjustContract = async ({ id, remaining_balance, risk_score, note }) => (await apiClient.post(`/admin/installments/contracts/${id}/adjustment`, { remaining_balance, risk_score, note })).data
const triggerReminder = async ({ id, type, channel }) => (await apiClient.post(`/admin/installments/contracts/${id}/reminder`, { type, channel })).data

export default function AdminInstallmentDashboardPage() {
  const queryClient = useQueryClient()
  const [planForm, setPlanForm] = useState({
    name: '',
    slug: '',
    device_price: '1000',
    down_payment: '200',
    installment_amount: '100',
    installment_count: '8',
    frequency: 'monthly',
    grace_period_days: '5',
    late_fee_type: 'fixed',
    late_fee_value: '10',
  })
  const [contractAction, setContractAction] = useState({ id: '', reason: '', note: '', remaining_balance: '', risk_score: '' })
  const { data: dashboard } = useQuery({ queryKey: ['admin-installment-dashboard'], queryFn: fetchDashboard })
  const { data: analytics } = useQuery({ queryKey: ['admin-installment-analytics'], queryFn: fetchAnalytics })
  const { data: plans } = useQuery({ queryKey: ['admin-installment-plans'], queryFn: fetchPlans })
  const { data: contracts } = useQuery({ queryKey: ['admin-installment-contracts'], queryFn: fetchContracts })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-installment-dashboard'] })
    queryClient.invalidateQueries({ queryKey: ['admin-installment-analytics'] })
    queryClient.invalidateQueries({ queryKey: ['admin-installment-plans'] })
    queryClient.invalidateQueries({ queryKey: ['admin-installment-contracts'] })
  }

  const createPlanMutation = useMutation({ mutationFn: createPlan, onSuccess: refresh })
  const approveMutation = useMutation({ mutationFn: approveContract, onSuccess: refresh })
  const defaulterMutation = useMutation({ mutationFn: markDefaulter, onSuccess: refresh })
  const recoveryMutation = useMutation({ mutationFn: addRecovery, onSuccess: refresh })
  const adjustMutation = useMutation({ mutationFn: adjustContract, onSuccess: refresh })
  const reminderMutation = useMutation({ mutationFn: triggerReminder, onSuccess: refresh })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Admin Installment Dashboard</h2>
      <div className="grid gap-3 md:grid-cols-3">
        <Card title="Plans" value={dashboard?.plans_total ?? 0} />
        <Card title="Contracts" value={dashboard?.contracts_total ?? 0} />
        <Card title="Defaulters" value={dashboard?.defaulters ?? 0} />
      </div>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Contract Status Analytics</h3>
        {analytics?.status_breakdown?.map((row) => <p key={row.status}>{row.status}: {row.total}</p>)}
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Plan Management</h3>
        <form
          className="grid gap-2 md:grid-cols-3"
          onSubmit={(e) => {
            e.preventDefault()
            createPlanMutation.mutate({
              ...planForm,
              device_price: Number(planForm.device_price),
              down_payment: Number(planForm.down_payment),
              installment_amount: Number(planForm.installment_amount),
              installment_count: Number(planForm.installment_count),
              grace_period_days: Number(planForm.grace_period_days),
              late_fee_value: Number(planForm.late_fee_value),
              is_active: true,
            })
          }}
        >
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Name" value={planForm.name} onChange={(e) => setPlanForm({ ...planForm, name: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Slug" value={planForm.slug} onChange={(e) => setPlanForm({ ...planForm, slug: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Device price" value={planForm.device_price} onChange={(e) => setPlanForm({ ...planForm, device_price: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Down payment" value={planForm.down_payment} onChange={(e) => setPlanForm({ ...planForm, down_payment: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Installment amount" value={planForm.installment_amount} onChange={(e) => setPlanForm({ ...planForm, installment_amount: e.target.value })} required />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Installment count" value={planForm.installment_count} onChange={(e) => setPlanForm({ ...planForm, installment_count: e.target.value })} required />
          <button type="submit" className="rounded bg-cyan-600 px-3 py-2 hover:bg-cyan-500">Create Plan</button>
        </form>
        <div className="mt-3 space-y-2">
          {plans?.data?.map((plan) => <p key={plan.id}>{plan.id} · {plan.name} · {plan.frequency} × {plan.installment_count}</p>)}
        </div>
      </section>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <h3 className="mb-2 font-medium">Contract Actions</h3>
        <div className="grid gap-2 md:grid-cols-4">
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Contract ID" value={contractAction.id} onChange={(e) => setContractAction({ ...contractAction, id: e.target.value })} />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Defaulter reason" value={contractAction.reason} onChange={(e) => setContractAction({ ...contractAction, reason: e.target.value })} />
          <input className="rounded bg-slate-800 px-3 py-2" placeholder="Recovery/Adjustment note" value={contractAction.note} onChange={(e) => setContractAction({ ...contractAction, note: e.target.value })} />
          <div className="flex gap-2">
            <input className="w-1/2 rounded bg-slate-800 px-3 py-2" placeholder="Balance" value={contractAction.remaining_balance} onChange={(e) => setContractAction({ ...contractAction, remaining_balance: e.target.value })} />
            <input className="w-1/2 rounded bg-slate-800 px-3 py-2" placeholder="Risk" value={contractAction.risk_score} onChange={(e) => setContractAction({ ...contractAction, risk_score: e.target.value })} />
          </div>
        </div>
        <div className="mt-3 flex flex-wrap gap-2">
          <button type="button" onClick={() => approveMutation.mutate(Number(contractAction.id))} className="rounded bg-cyan-600 px-3 py-1 hover:bg-cyan-500">Approve</button>
          <button type="button" onClick={() => defaulterMutation.mutate({ id: Number(contractAction.id), reason: contractAction.reason })} className="rounded bg-rose-700 px-3 py-1">Mark Defaulter</button>
          <button type="button" onClick={() => recoveryMutation.mutate({ id: Number(contractAction.id), note: contractAction.note || 'Recovery initiated' })} className="rounded bg-slate-700 px-3 py-1">Add Recovery Note</button>
          <button type="button" onClick={() => adjustMutation.mutate({ id: Number(contractAction.id), remaining_balance: Number(contractAction.remaining_balance) || undefined, risk_score: Number(contractAction.risk_score) || undefined, note: contractAction.note || undefined })} className="rounded bg-slate-700 px-3 py-1">Adjust</button>
          <button type="button" onClick={() => reminderMutation.mutate({ id: Number(contractAction.id), type: 'upcoming', channel: 'email' })} className="rounded bg-slate-700 px-3 py-1">Trigger Reminder</button>
        </div>
        <div className="mt-3 space-y-2">
          {contracts?.data?.map((contract) => <p key={contract.id}>{contract.id} · {contract.contract_number} · {contract.status} · {contract.user?.email}</p>)}
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
