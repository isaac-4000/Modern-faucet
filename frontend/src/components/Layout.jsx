import React from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { 
  Coins, 
  Dice6, 
  Home, 
  LogIn, 
  LogOut, 
  Settings, 
  User,
  Bitcoin,
  Wallet
} from 'lucide-react'

const Layout = ({ children }) => {
  const { user, admin, logout, logoutAdmin } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()

  const handleLogout = async () => {
    if (admin) {
      await logoutAdmin()
      navigate('/admin/login')
    } else {
      await logout()
      navigate('/')
    }
  }

  const isActive = (path) => location.pathname === path

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
      {/* Header */}
      <header className="bg-white dark:bg-gray-800 shadow-lg border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* Logo */}
            <Link to="/" className="flex items-center space-x-2">
              <Bitcoin className="h-8 w-8 text-orange-500" />
              <span className="text-xl font-bold text-gray-900 dark:text-white">
                Modern Faucet
              </span>
            </Link>

            {/* Navigation */}
            <nav className="hidden md:flex items-center space-x-8">
              <Link
                to="/"
                className={`flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                  isActive('/') 
                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                    : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
                }`}
              >
                <Home className="h-4 w-4" />
                <span>Início</span>
              </Link>

              {user && (
                <>
                  <Link
                    to="/faucet"
                    className={`flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      isActive('/faucet') 
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
                    }`}
                  >
                    <Coins className="h-4 w-4" />
                    <span>Faucet</span>
                  </Link>

                  <Link
                    to="/dice"
                    className={`flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      isActive('/dice') 
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
                    }`}
                  >
                    <Dice6 className="h-4 w-4" />
                    <span>Jogo de Dados</span>
                  </Link>

                  <Link
                    to="/withdraw"
                    className={`flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                      isActive('/withdraw') 
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
                    }`}
                  >
                    <Wallet className="h-4 w-4" />
                    <span>Sacar</span>
                  </Link>
                </>
              )}

              {admin && (
                <Link
                  to="/admin/dashboard"
                  className={`flex items-center space-x-1 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                    isActive('/admin/dashboard') 
                      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
                      : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
                  }`}
                >
                  <Settings className="h-4 w-4" />
                  <span>Painel Admin</span>
                </Link>
              )}
            </nav>

            {/* User Actions */}
            <div className="flex items-center space-x-4">
              {user && (
                <div className="hidden md:flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-300">
                  <User className="h-4 w-4" />
                  <span className="font-medium">
                    {Number(user.balance ?? 0).toFixed(2)} pontos
                  </span>
                </div>
              )}

              {user || admin ? (
                <Button
                  onClick={handleLogout}
                  variant="outline"
                  size="sm"
                  className="flex items-center space-x-1"
                >
                  <LogOut className="h-4 w-4" />
                  <span>Sair</span>
                </Button>
              ) : (
                <div className="flex items-center space-x-2">
                  <Link to="/login">
                    <Button variant="outline" size="sm" className="flex items-center space-x-1">
                      <LogIn className="h-4 w-4" />
                      <span>Entrar</span>
                    </Button>
                  </Link>
                  <Link to="/register">
                    <Button size="sm">Registrar</Button>
                  </Link>
                </div>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-1">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            {children}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="flex items-center space-x-2 mb-4 md:mb-0">
              <Bitcoin className="h-5 w-5 text-orange-500" />
              <span className="text-sm text-gray-600 dark:text-gray-300">
                © 2024 Modern Faucet. Todos os direitos reservados.
              </span>
            </div>
            <div className="flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-300">
              <span>Faucet de Criptomoedas</span>
              <span>•</span>
              <span>Jogo de Dados Provably Fair</span>
              <span>•</span>
              <Link to="/admin/login" className="hover:text-blue-600 dark:hover:text-blue-400">
                Admin
              </Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}

export default Layout

