import { createContext, useContext, useMemo, useState } from 'react'
import { apiClient, setAuthToken } from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const raw = localStorage.getItem('imi_user')
    return raw ? JSON.parse(raw) : null
  })

  const login = async (email, password) => {
    const { data } = await apiClient.post('/auth/login', { email, password })
    setAuthToken(data.token)
    localStorage.setItem('imi_user', JSON.stringify(data.user))
    setUser(data.user)
    return data.user
  }

  const logout = async () => {
    try {
      await apiClient.post('/auth/logout')
    } finally {
      setAuthToken(null)
      localStorage.removeItem('imi_user')
      setUser(null)
    }
  }

  const value = useMemo(() => ({ user, setUser, login, logout }), [user])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export const useAuth = () => {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }

  return context
}
