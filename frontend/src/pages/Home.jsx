import React from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { 
  Coins, 
  Dice6, 
  Shield, 
  Zap, 
  Users, 
  TrendingUp,
  Bitcoin,
  ArrowRight
} from 'lucide-react'

const Home = () => {
  const { user } = useAuth()

  const features = [
    {
      icon: Coins,
      title: 'Faucet Gratuita',
      description: 'Ganhe Bitcoin grátis a cada 30 minutos. Sem truques, sem pegadinhas.',
    },
    {
      icon: Dice6,
      title: 'Jogo de Dados',
      description: 'Multiplique seus ganhos com nosso jogo de dados provably fair.',
    },
    {
      icon: Shield,
      title: 'Provably Fair',
      description: 'Todos os jogos são verificáveis e transparentes.',
    },
    {
      icon: Zap,
      title: 'Pagamentos Rápidos',
      description: 'Saques processados rapidamente para sua carteira.',
    },
    {
      icon: Users,
      title: 'Sistema de Referência',
      description: 'Ganhe 10% de comissão por cada amigo que você indicar.',
    },
    {
      icon: TrendingUp,
      title: 'Sem Limites',
      description: 'Não há limite para quanto você pode ganhar.',
    },
  ]

  const stats = [
    { label: 'Usuários Ativos', value: '10,000+' },
    { label: 'Bitcoin Distribuído', value: '5.2 BTC' },
    { label: 'Jogos Jogados', value: '50,000+' },
    { label: 'Uptime', value: '99.9%' },
  ]

  return (
    <div className="space-y-12">
      {/* Hero Section */}
      <div className="text-center py-12 px-6">
        <div className="flex justify-center mb-6">
          <div className="p-4 bg-orange-100 dark:bg-orange-900 rounded-full">
            <Bitcoin className="h-16 w-16 text-orange-500" />
          </div>
        </div>
        <h1 className="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
          Ganhe Bitcoin
          <span className="text-orange-500"> Grátis</span>
        </h1>
        <p className="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
          A faucet de Bitcoin mais moderna e confiável. Ganhe Bitcoin grátis a cada 30 minutos 
          e multiplique seus ganhos com nosso jogo de dados provably fair.
        </p>
        
        {user ? (
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/faucet">
              <Button size="lg" className="w-full sm:w-auto">
                <Coins className="mr-2 h-5 w-5" />
                Ir para Faucet
              </Button>
            </Link>
            <Link to="/dice">
              <Button variant="outline" size="lg" className="w-full sm:w-auto">
                <Dice6 className="mr-2 h-5 w-5" />
                Jogar Dados
              </Button>
            </Link>
          </div>
        ) : (
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/register">
              <Button size="lg" className="w-full sm:w-auto">
                Começar Agora
                <ArrowRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
            <Link to="/login">
              <Button variant="outline" size="lg" className="w-full sm:w-auto">
                Já tenho conta
              </Button>
            </Link>
          </div>
        )}
      </div>

      {/* Stats Section */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-6 px-6">
        {stats.map((stat, index) => (
          <Card key={index} className="text-center">
            <CardContent className="pt-6">
              <div className="text-2xl md:text-3xl font-bold text-orange-500 mb-2">
                {stat.value}
              </div>
              <div className="text-sm text-gray-600 dark:text-gray-300">
                {stat.label}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Features Section */}
      <div className="px-6">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Por que escolher nossa faucet?
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
            Oferecemos a melhor experiência para ganhar Bitcoin grátis com segurança e transparência.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map((feature, index) => (
            <Card key={index} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-center space-x-3">
                  <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <feature.icon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                  </div>
                  <CardTitle className="text-lg">{feature.title}</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-base">
                  {feature.description}
                </CardDescription>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>

      {/* CTA Section */}
      {!user && (
        <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12 px-6 mx-6 rounded-lg">
          <div className="text-center">
            <h3 className="text-2xl md:text-3xl font-bold mb-4">
              Pronto para começar a ganhar Bitcoin?
            </h3>
            <p className="text-lg mb-8 opacity-90">
              Registre-se agora e comece a ganhar Bitcoin grátis em menos de 1 minuto.
            </p>
            <Link to="/register">
              <Button size="lg" variant="secondary">
                Criar Conta Grátis
                <ArrowRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
          </div>
        </div>
      )}
    </div>
  )
}

export default Home

