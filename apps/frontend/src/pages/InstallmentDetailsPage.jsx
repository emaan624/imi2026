import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useParams } from 'react-router-dom'
import { apiClient } from '../api/client'

const fetchContract = async (id) => (await apiClient.get(`/installments/contracts/${id}`)).data
const payInstallment = async ({ id, amount }) => (await apiClient.post(`/installments/contracts/${id}/pay`, { amount })).data

export default function InstallmentDetailsPage() {
  const { contractId } = useParams()
  const queryClient = useQueryClient()
  const { data: contract } = useQuery({ queryKey: ['contract', contractId], queryFn: () => fetchContract(contractId), enabled: Boolean(contractId) })

  const paymentMutation = useMutation({
    mutationFn: payInstallment,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['contract', contractId] }),
  })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Installment Details</h2>
      <section className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
        <p className="font-medium">{contract?.contract_number}</p>
        <p>Status: {contract?.status}</p>
        <p>Paid: {contract?.paid_amount}</p>
        <p>Remaining: {contract?.remaining_balance}</p>
        <button onClick={() => paymentMutation.mutate({ id: contractId, amount: 100 })} className="mt-3 rounded bg-cyan-600 px-3 py-1 hover:bg-cyan-500">Pay 100</button>
      </section>
    </div>
  )
}
