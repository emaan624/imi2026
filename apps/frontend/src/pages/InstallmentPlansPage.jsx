import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchPlans = async () => (await apiClient.get('/installments/plans')).data

export default function InstallmentPlansPage() {
  const { data: plans = [] } = useQuery({ queryKey: ['installment-plans'], queryFn: fetchPlans })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Installment Plans</h2>
      <div className="grid gap-3 md:grid-cols-2">
        {plans.map((plan) => (
          <section key={plan.id} className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
            <h3 className="font-medium">{plan.name}</h3>
            <p>Device Price: {plan.device_price}</p>
            <p>Down Payment: {plan.down_payment}</p>
            <p>{plan.frequency} × {plan.installment_count}</p>
            <p>Installment Amount: {plan.installment_amount}</p>
          </section>
        ))}
      </div>
    </div>
  )
}
