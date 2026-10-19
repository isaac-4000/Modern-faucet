import React, { useEffect, useState } from 'react'
import { Progress } from '@/components/ui/progress'
import { Droplets, Loader2 } from 'lucide-react'
import { useAuth } from '../contexts/AuthContext'

/*
 * A barra da torneira.
 *
 * Mostra quanto do teto diário de saques já saiu hoje. É pública: aparece
 * na home para quem ainda nem tem conta.
 *
 * Por que mostrar isso:
 *   Em faucet, a maior desconfiança do usuário é "será que eles pagam?".
 *   Uma barra que seca é a prova de que o dinheiro existe e está saindo —
 *   e avisa quando acabou, em vez de deixar a pessoa descobrir sozinha
 *   depois de juntar o mínimo.
 */
const TankBar = ({ tank: tankDeFora, className = '' }) => {
  const { API_BASE_URL } = useAuth()
  const [tank, setTank] = useState(tankDeFora || null)
  const [carregando, setCarregando] = useState(!tankDeFora)

  useEffect(() => {
    if (tankDeFora) {
      setTank(tankDeFora)
      return
    }

    let cancelado = false

    const buscar = async () => {
      try {
        const resposta = await fetch(`${API_BASE_URL}/faucet/tank`)
        if (resposta.ok) {
          const dados = await resposta.json()
          if (!cancelado) setTank(dados)
        }
      } catch (e) {
        // silencioso: a barra simplesmente não aparece
      } finally {
        if (!cancelado) setCarregando(false)
      }
    }

    buscar()

    return () => {
      cancelado = true
    }
  }, [API_BASE_URL, tankDeFora])

  if (carregando && !tank) {
    return (
      <div className={`flex items-center gap-2 text-sm text-gray-500 ${className}`}>
        <Loader2 className="h-4 w-4 animate-spin" />
        <span>Carregando a torneira...</span>
      </div>
    )
  }

  if (!tank) {
    return null
  }

  const vazio = tank.is_empty
  const restante = Number(tank.percent_remaining ?? 0)

  return (
    <div className={`space-y-2 ${className}`}>
      <div className="flex items-center justify-between text-sm">
        <span className="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
          <Droplets
            className={`h-4 w-4 ${vazio ? 'text-red-500' : 'text-blue-500'}`}
          />
          <span className="font-medium">Torneira de hoje</span>
        </span>
        <span className="text-gray-600 dark:text-gray-300">
          {vazio ? (
            <span className="font-semibold text-red-500">Secou</span>
          ) : (
            <>
              <span className="font-semibold">
                R$ {Number(tank.remaining_brl).toFixed(2)}
              </span>
              {' de R$ '}
              {Number(tank.daily_cap_brl).toFixed(2)}
            </>
          )}
        </span>
      </div>

      <Progress
        value={restante}
        className={`h-2.5 ${vazio ? '[&>div]:bg-red-500' : ''}`}
      />

      <p className="text-xs text-gray-500 dark:text-gray-400">
        {vazio
          ? 'O teto de saques de hoje acabou. O tanque enche de novo amanhã.'
          : `${tank.payouts_today ?? 0} saques pagos hoje · pague em ${tank.currency}`}
      </p>
    </div>
  )
}

export default TankBar
