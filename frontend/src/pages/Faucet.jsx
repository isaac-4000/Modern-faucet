import React, { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Progress } from '@/components/ui/progress'
import TurnstileWidget from '../components/TurnstileWidget'
import TankBar from '../components/TankBar'
import { Link } from 'react-router-dom'
import { 
  Coins, 
  Clock, 
  Users, 
  Copy, 
  CheckCircle,
  Bitcoin,
  Gift,
  Timer,
  Wallet
} from 'lucide-react'

const Faucet = () => {
  const { user, token, API_BASE_URL } = useAuth()
  const [faucetInfo, setFaucetInfo] = useState(null)
  const [loading, setLoading] = useState(false)
  const [claiming, setClaiming] = useState(false)
  const [message, setMessage] = useState('')
  const [timeLeft, setTimeLeft] = useState(0)
  const [copied, setCopied] = useState(false)
  const [captchaToken, setCaptchaToken] = useState('')
  const [captchaKey, setCaptchaKey] = useState(0)

  useEffect(() => {
    fetchFaucetInfo()
  }, [])

  useEffect(() => {
    let interval = null
    if (timeLeft > 0) {
      interval = setInterval(() => {
        setTimeLeft(timeLeft - 1)
      }, 1000)
    } else if (timeLeft === 0 && faucetInfo && !faucetInfo.can_claim) {
      fetchFaucetInfo()
    }
    return () => clearInterval(interval)
  }, [timeLeft, faucetInfo])

  const fetchFaucetInfo = async () => {
    setLoading(true)
    try {
      const response = await fetch(`${API_BASE_URL}/faucet/info`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      })
      
      if (response.ok) {
        const data = await response.json()
        setFaucetInfo(data)
        
        if (!data.can_claim && data.next_claim_at) {
          const nextClaim = new Date(data.next_claim_at)
          const now = new Date()
          const diff = Math.max(0, Math.floor((nextClaim - now) / 1000))
          setTimeLeft(diff)
        }
      }
    } catch (error) {
      console.error('Error fetching faucet info:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleClaim = async () => {
    // O captcha é resolvido uma vez por claim. Se algo falhar, a key muda
    // e o widget é recriado, obrigando a resolver de novo.
    if (!captchaToken) {
      setMessage('Confirme que você não é um robô antes de continuar.')
      return
    }

    setClaiming(true)
    setMessage('')
    
    try {
      const response = await fetch(`${API_BASE_URL}/faucet/claim`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          'cf-turnstile-response': captchaToken,
        }),
      })
      
      const data = await response.json()
      
      if (response.ok) {
        setMessage(`Sucesso! Você ganhou ${data.amount} pontos.`)
        setCaptchaToken('')
        setCaptchaKey((k) => k + 1)
        fetchFaucetInfo()
      } else {
        const primeiroErro = data.errors
          ? Object.values(data.errors).flat()[0]
          : null
        setMessage(primeiroErro || data.message || 'Erro ao fazer claim')
        setCaptchaToken('')
        setCaptchaKey((k) => k + 1)
      }
    } catch (error) {
      console.error('Error claiming:', error)
      setMessage('Erro de rede')
    } finally {
      setClaiming(false)
    }
  }

  const copyReferralUrl = () => {
    if (faucetInfo?.referral_url) {
      navigator.clipboard.writeText(faucetInfo.referral_url)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    }
  }

  const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60
    
    if (hours > 0) {
      return `${hours}h ${minutes}m ${secs}s`
    } else if (minutes > 0) {
      return `${minutes}m ${secs}s`
    } else {
      return `${secs}s`
    }
  }

  const getProgressPercentage = () => {
    if (!faucetInfo || faucetInfo.can_claim) return 100
    const totalInterval = faucetInfo.claim_interval_seconds
    const remaining = timeLeft
    return Math.max(0, ((totalInterval - remaining) / totalInterval) * 100)
  }

  if (loading && !faucetInfo) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="text-center">
        <div className="flex justify-center mb-4">
          <div className="p-4 bg-orange-100 dark:bg-orange-900 rounded-full">
            <Coins className="h-12 w-12 text-orange-500" />
          </div>
        </div>
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
          Faucet de Criptomoedas
        </h1>
        <p className="text-gray-600 dark:text-gray-300">
          Ganhe pontos a cada {Math.round((faucetInfo?.claim_interval_seconds || 240) / 60)} minutos e troque por{' '}
          {faucetInfo?.payout_currency || 'DGB'}
        </p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <TankBar tank={faucetInfo?.tank} />
        </CardContent>
      </Card>

      {message && (
        <Alert className={message.includes('Sucesso') ? 'border-green-500 bg-green-50 dark:bg-green-900' : ''}>
          <AlertDescription>{message}</AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Claim Section */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Gift className="h-5 w-5 text-orange-500" />
              <span>Fazer Claim</span>
            </CardTitle>
            <CardDescription>
              Ganhe {faucetInfo?.claim_amount} pontos grátis
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {faucetInfo?.can_claim ? (
              <div className="space-y-3">
                <TurnstileWidget key={captchaKey} onToken={setCaptchaToken} />
                <Button 
                  onClick={handleClaim} 
                  disabled={claiming || !captchaToken}
                  className="w-full h-12 text-lg"
                >
                  {claiming ? (
                    <div className="flex items-center space-x-2">
                      <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                      <span>Processando...</span>
                    </div>
                  ) : (
                    <div className="flex items-center space-x-2">
                      <Coins className="h-5 w-5" />
                      <span>
                        {captchaToken
                          ? `Resgatar ${faucetInfo?.claim_amount} pontos`
                          : 'Confirme que você não é um robô'}
                      </span>
                    </div>
                  )}
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                <div className="text-center">
                  <div className="flex items-center justify-center space-x-2 text-orange-500 mb-2">
                    <Timer className="h-5 w-5" />
                    <span className="text-lg font-semibold">
                      {formatTime(timeLeft)}
                    </span>
                  </div>
                  <p className="text-sm text-gray-600 dark:text-gray-300">
                    Próximo claim disponível em
                  </p>
                </div>
                
                <Progress value={getProgressPercentage()} className="h-3" />
                
                <Button disabled className="w-full h-12 text-lg">
                  <Clock className="h-5 w-5 mr-2" />
                  Aguarde para o próximo claim
                </Button>
              </div>
            )}

            <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-300">Seu saldo:</span>
                <span className="font-semibold text-orange-500">
                  {Number(user?.balance ?? 0).toFixed(2)} pontos
                </span>
              </div>
              <div className="pt-2">
                <Link to="/withdraw">
                  <Button variant="outline" className="w-full">
                    <Wallet className="h-4 w-4 mr-2" />
                    Sacar meus pontos
                  </Button>
                </Link>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Referral Section */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Users className="h-5 w-5 text-blue-500" />
              <span>Sistema de Referência</span>
            </CardTitle>
            <CardDescription>
              Ganhe 10% de comissão por cada amigo que você indicar
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                Seu link de referência:
              </label>
              <div className="flex space-x-2">
                <input
                  type="text"
                  value={faucetInfo?.referral_url || ''}
                  readOnly
                  className="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-mono"
                />
                <Button
                  onClick={copyReferralUrl}
                  variant="outline"
                  size="sm"
                  className="px-3"
                >
                  {copied ? (
                    <CheckCircle className="h-4 w-4 text-green-500" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                </Button>
              </div>
            </div>

            <div className="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
              <h4 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                Como funciona:
              </h4>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Compartilhe seu link de referência</li>
                <li>• Quando alguém se registrar, você ganha 10% de comissão</li>
                <li>• A comissão é creditada automaticamente</li>
                <li>• Sem limite de referências!</li>
              </ul>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center space-x-2">
              <Bitcoin className="h-8 w-8 text-orange-500" />
              <div>
                <p className="text-2xl font-bold text-orange-500">
                  {faucetInfo?.claim_amount}
                </p>
                <p className="text-sm text-gray-600 dark:text-gray-300">
                  Pontos por claim
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center space-x-2">
              <Clock className="h-8 w-8 text-blue-500" />
              <div>
                <p className="text-2xl font-bold text-blue-500">
                  {Math.round((faucetInfo?.claim_interval_seconds || 240) / 60)}
                </p>
                <p className="text-sm text-gray-600 dark:text-gray-300">
                  Minutos entre claims
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center space-x-2">
              <Users className="h-8 w-8 text-green-500" />
              <div>
                <p className="text-2xl font-bold text-green-500">10%</p>
                <p className="text-sm text-gray-600 dark:text-gray-300">
                  Comissão de referência
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

export default Faucet

