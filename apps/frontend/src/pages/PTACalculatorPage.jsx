import { useMutation } from '@tanstack/react-query'
import { useState } from 'react'
import { apiClient } from '../api/client'

const calculateTax = async (payload) => (await apiClient.post('/pta/calculate-tax', payload)).data

export default function PTACalculatorPage() {
  const [form, setForm] = useState({ imei_1: '', imei_2: '', sim_type: 'single' })
  const calculator = useMutation({ mutationFn: calculateTax })

  const submit = (event) => {
    event.preventDefault()
    calculator.mutate(form)
  }

  return (
    <div className="space-y-4">
      <h2 className="text-2xl font-semibold">PTA Calculator</h2>
      <form onSubmit={submit} className="grid gap-3 rounded border border-slate-800 bg-slate-900 p-4 md:grid-cols-2">
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="IMEI 1" value={form.imei_1} onChange={(e) => setForm({ ...form, imei_1: e.target.value })} required />
        <input className="rounded bg-slate-800 px-3 py-2" placeholder="IMEI 2 (Dual SIM)" value={form.imei_2} onChange={(e) => setForm({ ...form, imei_2: e.target.value })} />
        <select className="rounded bg-slate-800 px-3 py-2" value={form.sim_type} onChange={(e) => setForm({ ...form, sim_type: e.target.value })}>
          <option value="single">Single SIM</option>
          <option value="dual">Dual SIM</option>
        </select>
        <button type="submit" className="rounded bg-cyan-600 px-3 py-2 font-medium hover:bg-cyan-500">Calculate</button>
      </form>
      {calculator.data && (
        <div className="rounded border border-slate-800 bg-slate-900 p-4 text-sm">
          <p>Tax: {calculator.data.tax_amount}</p>
          <p>Service Fee: {calculator.data.service_fee}</p>
          <p className="font-semibold">Total: {calculator.data.total_amount}</p>
        </div>
      )}
    </div>
  )
}
