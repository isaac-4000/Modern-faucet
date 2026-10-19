import React, { useState, useEffect } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import TurnstileWidget from '../components/TurnstileWidget'
import { UserPlus, Bitcoin, Eye, EyeOff, Users } from 'lucide-react'

const Register = () => {
  const [walletAddress, setWalletAddress] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [referredBy, setReferredBy] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [errors, setErrors] = useState({})
  const [loading, setLoading] = useState(false)
  const [captchaToken, setCaptchaToken] = useState('')
  const [captchaKey, setCaptchaKey] = useState(0)
  
  const { register } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()

  useEffect(() => {
    const ref = searchParams.get('ref')
    if (ref) {
      setReferredBy(ref)
    }
  }, [searchParams])

  const validateForm = () => {
    const newErrors = {}

    if (!walletAddress) {
      newErrors.walletAddress = 'Endereço da carteira é obrigatório'
    } else if (!/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/.test(walletAddress)) {
      newErrors.walletAddress = 'Endereço de carteira Bitcoin inválido'
    }

    if (email && !/\S+@\S+\.\S+/.test(email)) {
      newErrors.email = 'Email inválido'
    }

    if (!password) {
      newErrors.password = 'Senha é obrigatória'
    } else if (password.length < 6) {
      newErrors.password = 'Senha deve ter pelo menos 6 caracteres'
    }

    if (password !== confirmPassword) {
      newErrors.confirmPassword = 'Senhas não coincidem'
    }

    if (!captchaToken) {
      newErrors.captcha = 'Confirme que você não é um robô'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  // O token do captcha vale só uma vez. Se o cadastro falhar, a gente
  // obriga o usuário a resolver de novo trocando a key do widget.
  const reiniciarCaptcha = () => {
    setCaptchaToken('')
    setCaptchaKey((k) => k + 1)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    
    if (!validateForm()) {
      return
    }

    setLoading(true)

    const result = await register(walletAddress, email, password, referredBy, captchaToken)

    if (result.success) {
      navigate('/faucet')
      return
    }

    reiniciarCaptcha()

    if (result.errors) {
      setErrors(result.errors)
    } else {
      setErrors({ general: result.message || 'Erro ao criar conta.' })
    }
    
    setLoading(false)
  }

  return (
    <div className="min-h-[700px] flex items-center justify-center p-6">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <div className="flex justify-center mb-4">
            <div className="p-3 bg-green-100 dark:bg-green-900 rounded-full">
              <UserPlus className="h-8 w-8 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <CardTitle className="text-2xl font-bold">Criar nova conta</CardTitle>
          <CardDescription>
            Registre-se para começar a ganhar Bitcoin grátis
          </CardDescription>
        </CardHeader>
        
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            {errors.general && (
              <Alert variant="destructive">
                <AlertDescription>{errors.general}</AlertDescription>
              </Alert>
            )}

            {referredBy && (
              <Alert>
                <Users className="h-4 w-4" />
                <AlertDescription>
                  Você foi indicado por um usuário! Você ganhará bônus de referência.
                </AlertDescription>
              </Alert>
            )}

            <div className="space-y-2">
              <Label htmlFor="walletAddress">Endereço da Carteira Bitcoin *</Label>
              <Input
                id="walletAddress"
                type="text"
                placeholder="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"
                value={walletAddress}
                onChange={(e) => setWalletAddress(e.target.value)}
                className={`font-mono text-sm ${errors.walletAddress ? 'border-red-500' : ''}`}
                required
              />
              {errors.walletAddress && (
                <p className="text-sm text-red-500">{errors.walletAddress[0] || errors.walletAddress}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email">Email (opcional)</Label>
              <Input
                id="email"
                type="email"
                placeholder="seu@email.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={errors.email ? 'border-red-500' : ''}
              />
              {errors.email && (
                <p className="text-sm text-red-500">{errors.email[0] || errors.email}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Senha *</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Digite sua senha"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className={errors.password ? 'border-red-500' : ''}
                  required
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                  onClick={() => setShowPassword(!showPassword)}
                >
                  {showPassword ? (
                    <EyeOff className="h-4 w-4 text-gray-400" />
                  ) : (
                    <Eye className="h-4 w-4 text-gray-400" />
                  )}
                </Button>
              </div>
              {errors.password && (
                <p className="text-sm text-red-500">{errors.password[0] || errors.password}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="confirmPassword">Confirmar Senha *</Label>
              <div className="relative">
                <Input
                  id="confirmPassword"
                  type={showConfirmPassword ? 'text' : 'password'}
                  placeholder="Confirme sua senha"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className={errors.confirmPassword ? 'border-red-500' : ''}
                  required
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                >
                  {showConfirmPassword ? (
                    <EyeOff className="h-4 w-4 text-gray-400" />
                  ) : (
                    <Eye className="h-4 w-4 text-gray-400" />
                  )}
                </Button>
              </div>
              {errors.confirmPassword && (
                <p className="text-sm text-red-500">{errors.confirmPassword}</p>
              )}
            </div>

            {referredBy && (
              <div className="space-y-2">
                <Label htmlFor="referredBy">Código de Referência</Label>
                <Input
                  id="referredBy"
                  type="text"
                  value={referredBy}
                  onChange={(e) => setReferredBy(e.target.value)}
                  className="bg-gray-50 dark:bg-gray-700"
                  readOnly
                />
              </div>
            )}

            <div className="space-y-2">
              <TurnstileWidget key={captchaKey} onToken={setCaptchaToken} />
              {errors.captcha && (
                <p className="text-sm text-red-500">{errors.captcha}</p>
              )}
            </div>

            <Button 
              type="submit" 
              className="w-full" 
              disabled={loading}
            >
              {loading ? (
                <div className="flex items-center space-x-2">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                  <span>Criando conta...</span>
                </div>
              ) : (
                <div className="flex items-center space-x-2">
                  <UserPlus className="h-4 w-4" />
                  <span>Criar Conta</span>
                </div>
              )}
            </Button>
          </form>

          <div className="mt-6 text-center">
            <p className="text-sm text-gray-600 dark:text-gray-300">
              Já tem uma conta?{' '}
              <Link 
                to="/login" 
                className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
              >
                Faça login aqui
              </Link>
            </p>
          </div>

          <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
              <Bitcoin className="h-4 w-4" />
              <span>Uma conta por conexão. Se você divide a internet com outras pessoas, avise o suporte.</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

export default Register

