import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchPlans = async () => (await apiClient.get('/installments/plans')).data
const createContract = async (planId) => (await apiClient.post(`/installments/plans/${planId}/contracts`, { auto_deduction: false })).data

export default function InstallmentPlansPage() {
  const queryClient = useQueryClient()
  const { data: plans = [] } = useQuery({ queryKey: ['installment-plans'], queryFn: fetchPlans })
  const contractMutation = useMutation({
    mutationFn: createContract,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['my-installments'] }),
  })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Installment Plans</h2>
      {contractMutation.data && <p className="rounded border border-emerald-700 bg-emerald-900/40 p-3 text-sm">Contract created: {contractMutation.data.contract_number}</p>}
      <div className="grid gap-3 md:grid-cols-2">
        {plans.map((plan) => (
          <section key={plan.id} className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
            <h3 className="font-medium">{plan.name}</h3>
            <p>Device Price: {plan.device_price}</p>
            <p>Down Payment: {plan.down_payment}</p>
            <p>{plan.frequency} × {plan.installment_count}</p>
            <p>Installment Amount: {plan.installment_amount}</p>
            <button type="button" onClick={() => contractMutation.mutate(plan.id)} className="mt-3 rounded bg-cyan-600 px-3 py-1 hover:bg-cyan-500">Apply</button>
          </section>
        ))}
      </div>
    </div>
  )
}
