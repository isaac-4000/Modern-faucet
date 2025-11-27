import React, { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Slider } from '@/components/ui/slider'
import { 
  Dice6, 
  Play, 
  History, 
  Shield,
  TrendingUp,
  TrendingDown,
  Bitcoin,
  Info
} from 'lucide-react'

const DiceGame = () => {
  const { user, token, API_BASE_URL } = useAuth()
  const [betAmount, setBetAmount] = useState('0.00000001')
  const [targetNumber, setTargetNumber] = useState([50])
  const [clientSeed, setClientSeed] = useState('')
  const [playing, setPlaying] = useState(false)
  const [lastResult, setLastResult] = useState(null)
  const [message, setMessage] = useState('')
  const [gameHistory, setGameHistory] = useState([])
  const [showHistory, setShowHistory] = useState(false)

  useEffect(() => {
    // Generate random client seed
    setClientSeed(Math.random().toString(36).substring(2, 15))
    fetchGameHistory()
  }, [])

  const fetchGameHistory = async () => {
    try {
      const response = await fetch(`${API_BASE_URL}/dice/history`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      })
      
      if (response.ok) {
        const data = await response.json()
        setGameHistory(data.data || [])
      }
    } catch (error) {
      console.error('Error fetching game history:', error)
    }
  }

  const calculateMultiplier = () => {
    return (99 / targetNumber[0]) * 0.98
  }

  const calculateWinChance = () => {
    return targetNumber[0] - 1
  }

  const calculatePayout = () => {
    return parseFloat(betAmount) * calculateMultiplier()
  }

  const handlePlay = async () => {
    if (!betAmount || parseFloat(betAmount) <= 0) {
      setMessage('Digite um valor de aposta válido')
      return
    }

    if (parseFloat(betAmount) > parseFloat(user.balance)) {
      setMessage('Saldo insuficiente')
      return
    }

    setPlaying(true)
    setMessage('')
    setLastResult(null)

    try {
      const response = await fetch(`${API_BASE_URL}/dice/play`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          bet_amount: parseFloat(betAmount),
          target_number: targetNumber[0],
          client_seed: clientSeed,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        setLastResult(data)
        setMessage(
          data.is_win 
            ? `Você ganhou! Resultado: ${data.roll_result.toFixed(2)} (< ${data.target_number})`
            : `Você perdeu! Resultado: ${data.roll_result.toFixed(2)} (>= ${data.target_number})`
        )
        
        // Generate new client seed for next game
        setClientSeed(Math.random().toString(36).substring(2, 15))
        
        // Refresh game history
        fetchGameHistory()
      } else {
        setMessage(data.message || 'Erro ao jogar')
      }
    } catch (error) {
      console.error('Error playing dice:', error)
      setMessage('Erro de rede')
    } finally {
      setPlaying(false)
    }
  }

  const formatBTC = (amount) => {
    return parseFloat(amount).toFixed(8)
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="text-center">
        <div className="flex justify-center mb-4">
          <div className="p-4 bg-purple-100 dark:bg-purple-900 rounded-full">
            <Dice6 className="h-12 w-12 text-purple-500" />
          </div>
        </div>
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          Jogo de Dados Provably Fair
        </h1>
        <p className="text-gray-600 dark:text-gray-300">
          Multiplique seus Bitcoin com nosso jogo transparente e verificável
        </p>
      </div>

      {message && (
        <Alert className={
          message.includes('ganhou') 
            ? 'border-green-500 bg-green-50 dark:bg-green-900' 
            : message.includes('perdeu')
            ? 'border-red-500 bg-red-50 dark:bg-red-900'
            : ''
        }>
          <AlertDescription>{message}</AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Game Controls */}
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Play className="h-5 w-5 text-purple-500" />
                <span>Controles do Jogo</span>
              </CardTitle>
              <CardDescription>
                Configure sua aposta e jogue
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Bet Amount */}
              <div className="space-y-2">
                <Label htmlFor="betAmount">Valor da Aposta (BTC)</Label>
                <Input
                  id="betAmount"
                  type="number"
                  step="0.00000001"
                  min="0.00000001"
                  max="0.00005000"
                  value={betAmount}
                  onChange={(e) => setBetAmount(e.target.value)}
                  className="font-mono"
                />
                <div className="flex space-x-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setBetAmount('0.00000001')}
                  >
                    Min
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setBetAmount((parseFloat(betAmount) * 2).toFixed(8))}
                  >
                    2x
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setBetAmount((parseFloat(betAmount) / 2).toFixed(8))}
                  >
                    1/2
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setBetAmount(user?.balance || '0')}
                  >
                    Max
                  </Button>
                </div>
              </div>

              {/* Target Number */}
              <div className="space-y-4">
                <Label>Número Alvo: {targetNumber[0]}</Label>
                <Slider
                  value={targetNumber}
                  onValueChange={setTargetNumber}
                  max={97}
                  min={2}
                  step={1}
                  className="w-full"
                />
                <div className="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                  <span>2 (Maior risco)</span>
                  <span>97 (Menor risco)</span>
                </div>
              </div>

              {/* Client Seed */}
              <div className="space-y-2">
                <Label htmlFor="clientSeed">Client Seed (para provably fair)</Label>
                <Input
                  id="clientSeed"
                  type="text"
                  value={clientSeed}
                  onChange={(e) => setClientSeed(e.target.value)}
                  className="font-mono text-sm"
                />
              </div>

              {/* Play Button */}
              <Button 
                onClick={handlePlay} 
                disabled={playing || !betAmount}
                className="w-full h-12 text-lg"
              >
                {playing ? (
                  <div className="flex items-center space-x-2">
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span>Jogando...</span>
                  </div>
                ) : (
                  <div className="flex items-center space-x-2">
                    <Dice6 className="h-5 w-5" />
                    <span>Jogar</span>
                  </div>
                )}
              </Button>
            </CardContent>
          </Card>

          {/* Last Result */}
          {lastResult && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  {lastResult.is_win ? (
                    <TrendingUp className="h-5 w-5 text-green-500" />
                  ) : (
                    <TrendingDown className="h-5 w-5 text-red-500" />
                  )}
                  <span>Último Resultado</span>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                  <div>
                    <p className="text-2xl font-bold text-purple-500">
                      {lastResult.roll_result.toFixed(2)}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-300">Resultado</p>
                  </div>
                  <div>
                    <p className="text-2xl font-bold">
                      {lastResult.target_number}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-300">Alvo</p>
                  </div>
                  <div>
                    <p className={`text-2xl font-bold ${lastResult.is_win ? 'text-green-500' : 'text-red-500'}`}>
                      {formatBTC(lastResult.payout_amount)}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-300">Pagamento</p>
                  </div>
                  <div>
                    <p className={`text-2xl font-bold ${lastResult.net_profit >= 0 ? 'text-green-500' : 'text-red-500'}`}>
                      {lastResult.net_profit >= 0 ? '+' : ''}{formatBTC(lastResult.net_profit)}
                    </p>
                    <p className="text-sm text-gray-600 dark:text-gray-300">Lucro</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>

        {/* Game Info */}
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Info className="h-5 w-5 text-blue-500" />
                <span>Informações</span>
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <div className="flex justify-between">
                  <span className="text-sm text-gray-600 dark:text-gray-300">Chance de Vitória:</span>
                  <span className="font-semibold">{calculateWinChance().toFixed(2)}%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm text-gray-600 dark:text-gray-300">Multiplicador:</span>
                  <span className="font-semibold">{calculateMultiplier().toFixed(4)}x</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm text-gray-600 dark:text-gray-300">Pagamento:</span>
                  <span className="font-semibold text-green-500">
                    {formatBTC(calculatePayout())} BTC
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm text-gray-600 dark:text-gray-300">Seu Saldo:</span>
                  <span className="font-semibold text-orange-500">
                    {user?.balance} BTC
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Shield className="h-5 w-5 text-green-500" />
                <span>Provably Fair</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <p>• Todos os resultados são verificáveis</p>
                <p>• Server seed é revelado após o jogo</p>
                <p>• Você pode verificar a justiça de cada jogada</p>
                <p>• Algoritmo SHA-256 transparente</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <History className="h-5 w-5 text-gray-500" />
                <span>Histórico</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Button
                variant="outline"
                onClick={() => setShowHistory(!showHistory)}
                className="w-full"
              >
                {showHistory ? 'Ocultar' : 'Mostrar'} Histórico
              </Button>
              
              {showHistory && (
                <div className="mt-4 space-y-2 max-h-60 overflow-y-auto">
                  {gameHistory.length > 0 ? (
                    gameHistory.slice(0, 10).map((game, index) => (
                      <div
                        key={game.id}
                        className="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded text-sm"
                      >
                        <span className={game.is_win ? 'text-green-500' : 'text-red-500'}>
                          {game.roll_result.toFixed(2)}
                        </span>
                        <span>{game.target_number}</span>
                        <span className={game.is_win ? 'text-green-500' : 'text-red-500'}>
                          {game.is_win ? '+' : '-'}{formatBTC(Math.abs(game.payout_amount - game.bet_amount))}
                        </span>
                      </div>
                    ))
                  ) : (
                    <p className="text-center text-gray-500">Nenhum jogo ainda</p>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}

export default DiceGame

