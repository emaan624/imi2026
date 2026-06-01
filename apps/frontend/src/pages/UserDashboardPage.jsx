import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../api/client'

const fetchWallet = async () => (await apiClient.get('/wallet')).data
const fetchTransactions = async () => (await apiClient.get('/wallet/transactions')).data
const fetchTickets = async () => (await apiClient.get('/tickets')).data

export default function UserDashboardPage() {
  const { data: wallet } = useQuery({ queryKey: ['wallet'], queryFn: fetchWallet })
  const { data: transactions } = useQuery({ queryKey: ['transactions'], queryFn: fetchTransactions })
  const { data: tickets } = useQuery({ queryKey: ['tickets'], queryFn: fetchTickets })

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-semibold">User Dashboard</h2>
      <div className="grid gap-4 md:grid-cols-3">
        <section className="rounded border border-slate-800 bg-slate-900 p-4">
          <h3 className="mb-2 text-sm text-slate-400">Wallet Balance</h3>
          <p className="text-2xl font-bold">{wallet?.balance ?? '0.00'} {wallet?.currency ?? 'USD'}</p>
        </section>
        <section className="rounded border border-slate-800 bg-slate-900 p-4">
          <h3 className="mb-2 text-sm text-slate-400">Transactions</h3>
          <p className="text-2xl font-bold">{transactions?.total ?? 0}</p>
        </section>
        <section className="rounded border border-slate-800 bg-slate-900 p-4">
          <h3 className="mb-2 text-sm text-slate-400">Support Tickets</h3>
          <p className="text-2xl font-bold">{tickets?.total ?? 0}</p>
        </section>
      </div>
    </div>
  )
}
