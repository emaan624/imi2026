import { useQuery } from '@tanstack/react-query'
import { useParams } from 'react-router-dom'
import { apiClient } from '../api/client'

const fetchSchedule = async (id) => (await apiClient.get(`/installments/contracts/${id}/schedule`)).data

export default function PaymentSchedulePage() {
  const { contractId } = useParams()
  const { data: schedule = [] } = useQuery({ queryKey: ['schedule', contractId], queryFn: () => fetchSchedule(contractId), enabled: Boolean(contractId) })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">Payment Schedule</h2>
      <div className="space-y-2">
        {schedule.map((item) => (
          <div key={item.id} className="rounded border border-slate-800 bg-slate-900 p-3 text-sm">
            <p>Installment #{item.installment_no}</p>
            <p>Due: {item.due_date}</p>
            <p>Amount: {item.amount}</p>
            <p>Status: {item.status}</p>
          </div>
        ))}
      </div>
    </div>
  )
}
