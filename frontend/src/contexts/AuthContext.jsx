import React, { createContext, useContext, useState, useEffect } from 'react'

const AuthContext = createContext()

export const useAuth = () => {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null)
  const [admin, setAdmin] = useState(null)
  const [token, setToken] = useState(localStorage.getItem('token'))
  const [adminToken, setAdminToken] = useState(localStorage.getItem('adminToken'))
  const [loading, setLoading] = useState(true)

  const API_BASE_URL = 'http://localhost:8000/api'

  useEffect(() => {
    if (token) {
      fetchUser()
    } else if (adminToken) {
      fetchAdmin()
    } else {
      setLoading(false)
    }
  }, [token, adminToken])

  const fetchUser = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/user`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      })
      if (response.ok) {
        const userData = await response.json()
        setUser(userData)
      } else {
        logout()
      }
    } catch (error) {
      console.error('Error fetching user:', error)
      logout()
    } finally {
      setLoading(false)
    }
  }

  const fetchAdmin = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/admin/dashboard`, {
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json',
        },
      })
      if (response.ok) {
        setAdmin({ token: adminToken })
      } else {
        logoutAdmin()
      }
    } catch (error) {
      console.error('Error fetching admin:', error)
      logoutAdmin()
    } finally {
      setLoading(false)
    }
  }

  const login = async (walletAddress, password) => {
    try {
      const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          wallet_address: walletAddress,
          password: password,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        setUser(data.user)
        setToken(data.token)
        localStorage.setItem('token', data.token)
        return { success: true }
      } else {
        return { success: false, message: data.message || 'Login failed' }
      }
    } catch (error) {
      console.error('Login error:', error)
      return { success: false, message: 'Network error' }
    }
  }

  const register = async (walletAddress, email, password, referredBy) => {
    try {
      const response = await fetch(`${API_BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          wallet_address: walletAddress,
          email: email,
          password: password,
          referred_by: referredBy,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        setUser(data.user)
        setToken(data.token)
        localStorage.setItem('token', data.token)
        return { success: true }
      } else {
        return { success: false, errors: data.errors }
      }
    } catch (error) {
      console.error('Register error:', error)
      return { success: false, message: 'Network error' }
    }
  }

  const loginAdmin = async (username, password) => {
    try {
      const response = await fetch(`${API_BASE_URL}/admin/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: username,
          password: password,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        setAdmin(data.admin)
        setAdminToken(data.token)
        localStorage.setItem('adminToken', data.token)
        return { success: true }
      } else {
        return { success: false, message: data.message || 'Login failed' }
      }
    } catch (error) {
      console.error('Admin login error:', error)
      return { success: false, message: 'Network error' }
    }
  }

  const logout = async () => {
    try {
      if (token) {
        await fetch(`${API_BASE_URL}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        })
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      setUser(null)
      setToken(null)
      localStorage.removeItem('token')
    }
  }

  const logoutAdmin = async () => {
    try {
      if (adminToken) {
        await fetch(`${API_BASE_URL}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Content-Type': 'application/json',
          },
        })
      }
    } catch (error) {
      console.error('Admin logout error:', error)
    } finally {
      setAdmin(null)
      setAdminToken(null)
      localStorage.removeItem('adminToken')
    }
  }

  const value = {
    user,
    admin,
    token,
    adminToken,
    loading,
    login,
    register,
    loginAdmin,
    logout,
    logoutAdmin,
    API_BASE_URL,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

