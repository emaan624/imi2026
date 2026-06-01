import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('admin@imi.local')
  const [password, setPassword] = useState('password')
  const [error, setError] = useState('')

  const onSubmit = async (event) => {
    event.preventDefault()
    setError('')

    try {
      const user = await login(email, password)
      navigate(user.role === 'admin' ? '/admin' : '/dashboard')
    } catch {
      setError('Invalid credentials')
    }
  }

  return (
    <div className="mx-auto mt-16 max-w-md rounded-lg border border-slate-800 bg-slate-900 p-6">
      <h2 className="mb-4 text-2xl font-semibold">Login</h2>
      <form onSubmit={onSubmit} className="space-y-4">
        <div>
          <label className="mb-1 block text-sm">Email</label>
          <input className="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2" value={email} onChange={(e) => setEmail(e.target.value)} />
        </div>
        <div>
          <label className="mb-1 block text-sm">Password</label>
          <input type="password" className="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2" value={password} onChange={(e) => setPassword(e.target.value)} />
        </div>
        {error && <p className="text-sm text-rose-400">{error}</p>}
        <button type="submit" className="w-full rounded bg-cyan-600 px-4 py-2 font-medium hover:bg-cyan-500">Sign in</button>
      </form>
    </div>
  )
}
