import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Progress } from '@/components/ui/progress'
import TankBar from '../components/TankBar'
import { Wallet, ArrowRight, CheckCircle2, AlertTriangle, Coins, Clock } from 'lucide-react'

/*
 * Tela de saque.
 *
 * O pagamento sai pela FaucetPay (microcarteira), não direto para a carteira
 * do usuário. Mandar 0,03 DGB direto custaria mais em taxa de rede do que o
 * próprio pagamento — é por isso que nenhum faucet sério faz isso.
 *
 * Duas regras aparecem na tela de forma bem visível, porque são as duas
 * perguntas que todo usuário de faucet faz:
 *   1. Qual é o mínimo?  (500 pontos)
 *   2. Tem dinheiro hoje? (a barra da torneira)
 */
const Withdraw = () => {
  const { user, token, API_BASE_URL } = useAuth()

  const [carteira, setCarteira] = useState('')
  const [info, setInfo] = useState(null)
  const [saques, setSaques] = useState([])
  const [carregando, setCarregando] = useState(true)
  const [enviando, setEnviando] = useState(false)
  const [mensagem, setMensagem] = useState(null) // { tipo: 'ok'|'erro', texto }

  const buscarTudo = async () => {
    try {
      const [respInfo, respSaques] = await Promise.all([
        fetch(`${API_BASE_URL}/faucet/info`, {
          headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
        }),
        fetch(`${API_BASE_URL}/withdrawals`, {
          headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
        }),
      ])

      if (respInfo.ok) setInfo(await respInfo.json())
      if (respSaques.ok) setSaques(await respSaques.json())
    } catch (e) {
      console.error('Erro ao carregar dados de saque:', e)
    } finally {
      setCarregando(false)
    }
  }

  useEffect(() => {
    buscarTudo()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const saldo = Number(user?.balance ?? 0)
  const minimo = Number(info?.minimum_payout_points ?? 500)
  const tank = info?.tank
  const podeSacar = saldo >= minimo && !tank?.is_empty

  const progresso = minimo > 0 ? Math.min(100, (saldo / minimo) * 100) : 0

  const handleSacar = async (e) => {
    e.preventDefault()
    setMensagem(null)

    if (!carteira.trim()) {
      setMensagem({ tipo: 'erro', texto: 'Informe sua carteira, e-mail ou usuário da FaucetPay.' })
      return
    }

    setEnviando(true)

    try {
      const resposta = await fetch(`${API_BASE_URL}/withdraw`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ wallet_address: carteira.trim() }),
      })

      const dados = await resposta.json()

      if (resposta.ok) {
        setMensagem({
          tipo: 'ok',
          texto: `Saque de ${dados.amount} ${dados.currency} enviado para ${dados.payout?.wallet_address}.`,
        })
        setCarteira('')
        await buscarTudo()
      } else {
        setMensagem({
          tipo: 'erro',
          texto: dados.message || dados.error || 'Não foi possível sacar agora.',
        })
        if (dados.tank) setInfo((antigo) => ({ ...antigo, tank: dados.tank }))
      }
    } catch (e) {
      setMensagem({ tipo: 'erro', texto: 'Erro de rede. Tente de novo.' })
    } finally {
      setEnviando(false)
    }
  }

  const formatarData = (data) => {
    if (!data) return '—'
    return new Date(data).toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const corDoStatus = (status) => {
    if (status === 'completed') return 'text-green-600 dark:text-green-400'
    if (status === 'failed') return 'text-red-600 dark:text-red-400'
    return 'text-amber-600 dark:text-amber-400'
  }

  const textoDoStatus = (status) => {
    if (status === 'completed') return 'Pago'
    if (status === 'failed') return 'Falhou'
    return 'Processando'
  }

  if (!user) {
    return (
      <div className="p-6 max-w-md mx-auto text-center space-y-4">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          Entre para sacar
        </h1>
        <p className="text-gray-600 dark:text-gray-300">
          Faça login para ver seus pontos e pedir um saque.
        </p>
        <Link to="/login">
          <Button className="w-full">Entrar</Button>
        </Link>
      </div>
    )
  }

  if (carregando) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600" />
      </div>
    )
  }

  return (
    <div className="p-4 sm:p-6 space-y-6 max-w-4xl mx-auto">
      <div className="text-center">
        <div className="flex justify-center mb-4">
          <div className="p-4 bg-green-100 dark:bg-green-900 rounded-full">
            <Wallet className="h-12 w-12 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
          Sacar
        </h1>
        <p className="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
          Seus pontos viram {info?.payout_currency || 'DGB'} na sua conta da FaucetPay
        </p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <TankBar tank={tank} />
        </CardContent>
      </Card>

      {mensagem && (
        <Alert
          className={
            mensagem.tipo === 'ok'
              ? 'border-green-500 bg-green-50 dark:bg-green-900'
              : 'border-red-500 bg-red-50 dark:bg-red-900'
          }
        >
          {mensagem.tipo === 'ok' ? (
            <CheckCircle2 className="h-4 w-4 text-green-600" />
          ) : (
            <AlertTriangle className="h-4 w-4 text-red-600" />
          )}
          <AlertDescription>{mensagem.texto}</AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Pedir saque */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Coins className="h-5 w-5 text-green-600" />
              <span>Pedir saque</span>
            </CardTitle>
            <CardDescription>
              Mínimo de {minimo} pontos
              {info?.minimum_payout_crypto
                ? ` (≈ ${info.minimum_payout_crypto} ${info.payout_currency})`
                : ''}
            </CardDescription>
          </CardHeader>

          <CardContent className="space-y-4">
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-300">Seu saldo</span>
                <span className="font-semibold text-green-600 dark:text-green-400">
                  {saldo.toFixed(2)} pontos
                </span>
              </div>
              <Progress value={progresso} className="h-2" />
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {saldo >= minimo
                  ? 'Você já pode sacar.'
                  : `Faltam ${(minimo - saldo).toFixed(2)} pontos para o mínimo.`}
              </p>
            </div>

            <form onSubmit={handleSacar} className="space-y-3 pt-2 border-t border-gray-200 dark:border-gray-700">
              <div className="space-y-1.5">
                <Label htmlFor="carteira">Carteira da FaucetPay</Label>
                <Input
                  id="carteira"
                  value={carteira}
                  onChange={(e) => setCarteira(e.target.value)}
                  placeholder="e-mail, usuário ou endereço"
                  autoComplete="off"
                  autoCapitalize="none"
                  spellCheck="false"
                />
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  Ainda não tem conta?{' '}
                  <a
                    href="https://faucetpay.io/?r=register"
                    target="_blank"
                    rel="noreferrer noopener"
                    className="text-blue-600 underline"
                  >
                    Crie uma grátis
                  </a>
                  . É lá que o dinheiro cai.
                </p>
              </div>

              <Button
                type="submit"
                disabled={enviando || !podeSacar}
                className="w-full h-12 text-base"
              >
                {enviando ? (
                  <span className="flex items-center space-x-2">
                    <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-white" />
                    <span>Enviando...</span>
                  </span>
                ) : (
                  <span className="flex items-center space-x-2">
                    <span>Sacar agora</span>
                    <ArrowRight className="h-4 w-4" />
                  </span>
                )}
              </Button>

              {!podeSacar && (
                <p className="text-xs text-center text-gray-500 dark:text-gray-400">
                  {tank?.is_empty
                    ? 'A torneira de hoje secou. Volta amanhã.'
                    : 'Junte mais pontos para liberar o saque.'}
                </p>
              )}
            </form>
          </CardContent>
        </Card>

        {/* Como funciona */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Clock className="h-5 w-5 text-blue-600" />
              <span>Como funciona</span>
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm text-gray-700 dark:text-gray-200">
            <p>
              1. Você junta pontos no faucet e no jogo de dados.
            </p>
            <p>
              2. Ao pedir o saque, os pontos viram {info?.payout_currency || 'DGB'} na
              cotação do dia e vão para sua conta da FaucetPay.
            </p>
            <p>
              3. Da FaucetPay você saca para a carteira que quiser. Lá a taxa de
              saque é uma só — é por isso que não mandamos direto para você.
            </p>
            <div className="bg-blue-50 dark:bg-blue-950 p-3 rounded-lg text-blue-900 dark:text-blue-100">
              <p className="font-semibold mb-1">Por que existe um teto diário?</p>
              <p className="text-xs leading-relaxed">
                Pagamos até um valor fixo por dia, e a barra acima mostra quanto
                ainda tem. Se acabar, é só esperar o tanque encher de novo.
                Assim a gente não promete o que não pode cumprir.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Histórico */}
      <Card>
        <CardHeader>
          <CardTitle>Seus saques</CardTitle>
          <CardDescription>Últimos 50 pedidos</CardDescription>
        </CardHeader>
        <CardContent>
          {saques.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Você ainda não sacou nada.{' '}
              <Link to="/faucet" className="text-blue-600 underline">
                Ir para o faucet
              </Link>
              .
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th className="py-2 pr-3">Quando</th>
                    <th className="py-2 pr-3">Valor</th>
                    <th className="py-2 pr-3">Pontos</th>
                    <th className="py-2 pr-3">Destino</th>
                    <th className="py-2">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {saques.map((s) => (
                    <tr
                      key={s.id}
                      className="border-b border-gray-100 dark:border-gray-800"
                    >
                      <td className="py-2 pr-3 whitespace-nowrap">
                        {formatarData(s.created_at)}
                      </td>
                      <td className="py-2 pr-3 whitespace-nowrap font-mono">
                        {s.amount} {s.currency}
                      </td>
                      <td className="py-2 pr-3 whitespace-nowrap">
                        {Number(s.points_amount ?? 0).toFixed(2)}
                      </td>
                      <td className="py-2 pr-3 max-w-[140px] truncate">
                        {s.wallet_address}
                      </td>
                      <td className={`py-2 ${corDoStatus(s.status)}`}>
                        {textoDoStatus(s.status)}
                        {s.status === 'failed' && s.error_message && (
                          <span className="block text-xs text-gray-400 truncate">
                            {s.error_message}
                          </span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}

export default Withdraw
