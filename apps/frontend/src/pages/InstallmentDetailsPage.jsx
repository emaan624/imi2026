import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { apiClient } from '../api/client'

const fetchContract = async (id) => (await apiClient.get(`/installments/contracts/${id}`)).data
const payInstallment = async ({ id, amount }) => (await apiClient.post(`/installments/contracts/${id}/pay`, { amount })).data
const settleEarly = async (id) => (await apiClient.post(`/installments/contracts/${id}/settle-early`)).data
const fetchHistory = async (id) => (await apiClient.get(`/installments/contracts/${id}/history`)).data
const fetchRemainingBalance = async (id) => (await apiClient.get(`/installments/contracts/${id}/remaining-balance`)).data
const fetchStatement = async (id) => (await apiClient.get(`/installments/contracts/${id}/statement`)).data
const fetchAgreement = async (id) => (await apiClient.get(`/installments/contracts/${id}/agreement`)).data

export default function InstallmentDetailsPage() {
  const { contractId } = useParams()
  const queryClient = useQueryClient()
  const [amount, setAmount] = useState('100')
  const { data: contract } = useQuery({ queryKey: ['contract', contractId], queryFn: () => fetchContract(contractId), enabled: Boolean(contractId) })
  const { data: history = [] } = useQuery({ queryKey: ['contract-history', contractId], queryFn: () => fetchHistory(contractId), enabled: Boolean(contractId) })
  const { data: remaining } = useQuery({ queryKey: ['contract-remaining', contractId], queryFn: () => fetchRemainingBalance(contractId), enabled: Boolean(contractId) })
  const { data: statement } = useQuery({ queryKey: ['contract-statement', contractId], queryFn: () => fetchStatement(contractId), enabled: Boolean(contractId) })
  const { data: agreement } = useQuery({ queryKey: ['contract-agreement', contractId], queryFn: () => fetchAgreement(contractId), enabled: Boolean(contractId) })

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: ['contract', contractId] })
    queryClient.invalidateQueries({ queryKey: ['my-installments'] })
    queryClient.invalidateQueries({ queryKey: ['schedule', contractId] })
    queryClient.invalidateQueries({ queryKey: ['contract-history', contractId] })
    queryClient.invalidateQueries({ queryKey: ['contract-remaining', contractId] })
    queryClient.invalidateQueries({ queryKey: ['contract-statement', contractId] })
  }

  const paymentMutation = useMutation({
    mutationFn: payInstallment,
    onSuccess: refresh,
  })
  const settleMutation = useMutation({
    mutationFn: settleEarly,
    onSuccess: refresh,
  })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Installment Details</h2>
      <div className="grid gap-4 md:grid-cols-2">
        <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
          <p className="font-medium">{contract?.contract_number}</p>
          <p>Status: {contract?.status}</p>
          <p>Paid: {contract?.paid_amount}</p>
          <p>Remaining: {contract?.remaining_balance}</p>
          <p>Next Due: {remaining?.next_due_date || 'N/A'}</p>
          <div className="mt-3 flex gap-2">
            <input className="w-full rounded bg-slate-800 px-3 py-2" value={amount} onChange={(e) => setAmount(e.target.value)} />
            <button onClick={() => paymentMutation.mutate({ id: contractId, amount: Number(amount) })} className="rounded bg-cyan-600 px-3 py-1 hover:bg-cyan-500">Pay</button>
            <button onClick={() => settleMutation.mutate(contractId)} className="rounded bg-slate-700 px-3 py-1 hover:bg-slate-600">Settle</button>
          </div>
          {agreement && (
            <div className="pt-3">
              <p>Agreement: {agreement.agreement_title}</p>
              <p className="text-slate-400">{agreement.pdf_url}</p>
            </div>
          )}
        </section>
        <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
          <h3 className="mb-2 font-medium">Payment History</h3>
          {history.map((item) => (
            <p key={item.id}>{item.amount} ({item.status})</p>
          ))}
          <h4 className="mb-2 mt-4 font-medium">Statement Summary</h4>
          <p>Total Payments: {statement?.payments?.length || 0}</p>
          <p>Total Schedules: {statement?.schedules?.length || 0}</p>
        </section>
      </div>
    </div>
  )
}
