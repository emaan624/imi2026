import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { apiClient } from '../api/client'

const fetchContracts = async () => (await apiClient.get('/installments/contracts')).data

export default function MyInstallmentsPage() {
  const { data } = useQuery({ queryKey: ['my-installments'], queryFn: fetchContracts })

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">My Installments</h2>
      <div className="space-y-2">
        {data?.data?.map((contract) => (
          <div key={contract.id} className="rounded border border-slate-800 bg-slate-900 p-3 text-sm">
            <p className="font-medium">{contract.contract_number}</p>
            <p>Status: {contract.status}</p>
            <p>Remaining: {contract.remaining_balance}</p>
            <div className="mt-2 flex gap-3 text-cyan-300">
              <Link to={`/installments/${contract.id}`}>Details</Link>
              <Link to={`/installments/${contract.id}/schedule`}>Schedule</Link>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
