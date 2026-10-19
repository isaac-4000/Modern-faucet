import React, { useEffect, useRef, useState } from 'react'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { ShieldAlert } from 'lucide-react'

/*
 * Widget do Cloudflare Turnstile.
 *
 * Por que Turnstile e nao reCAPTCHA:
 *   - Gratuito e sem limite de requisições.
 *   - Não mostra aquelas imagens de semáforo que fazem metade da gente
 *     desistir. Na maioria das vezes o usuário nem vê nada.
 *   - Funciona bem no celular, que é onde a maior parte do tráfego de
 *     faucet está.
 *
 * Como usar no pai:
 *
 *   const [captchaToken, setCaptchaToken] = useState('')
 *   const [captchaKey, setCaptchaKey] = useState(0)   // muda para limpar
 *
 *   <TurnstileWidget key={captchaKey} onToken={setCaptchaToken} />
 *
 * O token tem validade curta (~5 min) e só serve uma vez. Se o envio
 * falhar, mude captchaKey para obrigar o usuário a resolver de novo.
 */

const SCRIPT_SRC =
  'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'

const SITE_KEY = import.meta.env.VITE_TURNSTILE_SITE_KEY || ''

let scriptPromise = null

function carregarScript() {
  if (scriptPromise) {
    return scriptPromise
  }

  scriptPromise = new Promise((resolve, reject) => {
    if (window.turnstile) {
      resolve(window.turnstile)
      return
    }

    const script = document.createElement('script')
    script.src = SCRIPT_SRC
    script.async = true
    script.defer = true
    script.onload = () => resolve(window.turnstile)
    script.onerror = () =>
      reject(new Error('Não foi possível carregar o captcha da Cloudflare.'))
    document.head.appendChild(script)
  })

  return scriptPromise
}

const TurnstileWidget = ({ onToken, onExpirar, className = '' }) => {
  const containerRef = useRef(null)
  const widgetIdRef = useRef(null)
  const onTokenRef = useRef(onToken)
  const onExpirarRef = useRef(onExpirar)

  const [estado, setEstado] = useState('carregando') // carregando | pronto | erro

  // Mantém as funções atualizadas sem recriar o widget a cada render.
  useEffect(() => {
    onTokenRef.current = onToken
    onExpirarRef.current = onExpirar
  }, [onToken, onExpirar])

  useEffect(() => {
    let cancelado = false

    if (!SITE_KEY) {
      setEstado('sem-chave')
      return
    }

    carregarScript()
      .then((turnstile) => {
        if (cancelado || !containerRef.current) {
          return
        }

        widgetIdRef.current = turnstile.render(containerRef.current, {
          sitekey: SITE_KEY,
          // flexible: ocupa a largura do pai. No celular isso importa muito.
          size: 'flexible',
          theme: 'auto',
          callback: (token) => {
            setEstado('pronto')
            if (onTokenRef.current) onTokenRef.current(token)
          },
          'expired-callback': () => {
            if (onTokenRef.current) onTokenRef.current('')
            if (onExpirarRef.current) onExpirarRef.current()
          },
          'error-callback': () => {
            setEstado('erro')
            if (onTokenRef.current) onTokenRef.current('')
          },
        })

        setEstado('pronto')
      })
      .catch(() => {
        if (!cancelado) setEstado('erro')
      })

    return () => {
      cancelado = true
      if (widgetIdRef.current && window.turnstile) {
        try {
          window.turnstile.remove(widgetIdRef.current)
        } catch (e) {
          // o widget já tinha saído da tela; não há o que limpar
        }
        widgetIdRef.current = null
      }
    }
  }, [])

  if (estado === 'sem-chave') {
    return (
      <Alert className={className}>
        <ShieldAlert className="h-4 w-4" />
        <AlertDescription className="text-xs">
          Captcha não configurado no frontend. Defina VITE_TURNSTILE_SITE_KEY
          no arquivo .env do frontend.
        </AlertDescription>
      </Alert>
    )
  }

  if (estado === 'erro') {
    return (
      <Alert variant="destructive" className={className}>
        <ShieldAlert className="h-4 w-4" />
        <AlertDescription className="text-xs">
          Não foi possível carregar a verificação de segurança. Verifique sua
          conexão e recarregue a página.
        </AlertDescription>
      </Alert>
    )
  }

  return (
    <div className={className}>
      <div ref={containerRef} className="cf-turnstile" />
      {estado === 'carregando' && (
        <div className="h-[65px] w-full animate-pulse rounded-md bg-gray-100 dark:bg-gray-800" />
      )}
    </div>
  )
}

export default TurnstileWidget
