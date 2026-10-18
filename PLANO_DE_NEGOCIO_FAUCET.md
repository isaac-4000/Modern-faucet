# Plano de Negócio — Faucet de Criptomoedas

**Para:** Isaac (`isaac-4000`)
**Data:** 16 de outubro de 2026 (atualizado em 17/10/2026 — ver capítulo 17: a queda do FreeBitco.in)
**Base:** números reais de mercado pesquisados nesta data. Fontes no final.
**Pré-requisito de leitura:** este plano assume que você leu — e aceitou — o diagnóstico técnico do projeto.

---

## 1. Veredito em uma página

**Um faucet é um negócio de mídia. Não é um negócio de criptomoeda.**

Isso muda tudo. Você não está vendendo Bitcoin — você está comprando atenção barata com Bitcoin e revendendo essa atenção para anunciantes a um preço maior. A criptomoeda é o **custo de aquisição de tráfego**, não o produto. Quem entende isso desde o dia 1 tem chance. Quem pensa que está "distribuindo cripto" quebra no terceiro mês.

**A conta que define se você ganha ou perde dinheiro:**

```
Receita por claim  =  impressões de anúncio × CPM ÷ 1000
Custo por claim    =  sats pagos ao usuário × preço do BTC ÷ 100.000.000

Se a primeira linha for maior que a segunda, você tem negócio.
Se for menor, você está pagando para trabalhar.
```

**O número que você precisa decorar: 1 usuário ativo por dia vale cerca de US$ 0,40 por mês de lucro líquido.**

Não US$ 4. Não US$ 40. **Quarenta centavos de dólar por mês.**

| Você quer faturar… | …então precisa de |
|---|---|
| US$ 100/mês (R$ 540) | ~250 usuários ativos/dia |
| US$ 400/mês (R$ 2.160) | ~1.000 usuários ativos/dia |
| 1 salário mínimo (~US$ 282) | ~710 usuários ativos/dia |
| US$ 2.000/mês (R$ 10.800) | ~5.000 usuários ativos/dia |

**A boa notícia:** os custos são baixíssimos. US$ 10 a 15 por mês. Faucet é um dos poucos negócios de internet onde o custo fixo é quase zero.
**A má notícia:** conseguir 1.000 pessoas voltando todo dia é *muito* difícil, leva de 9 a 18 meses, e é onde 95% dos faucets morrem.

**Veredito honesto:** como negócio principal, não. Como renda extra de longo prazo que paga uns R$ 1.500/mês depois de um ano de trabalho consistente, **é possível** — mas só se você tratar isso como operação séria, não como hobby. E há uma armadilha legal no Brasil (seção 8) que você **precisa** resolver por causa do jogo de dados.

---

## 2. O motor do negócio: as 5 fontes de receita

Todo faucet que dá lucro empolga **cinco** torneiras ao mesmo tempo. Nenhuma sozinha paga a conta.

| # | Fonte | Quanto paga | Peso no negócio |
|---|---|---|---|
| 1 | **Banners** (CPM) | US$ 0,30 – 8,00 por mil impressões | Base. Previsível, mas magro. |
| 2 | **Shortlinks** (antes do claim) | US$ 0,002 – 0,01 por clique | Bom. Soma muito no volume. |
| 3 | **Offer walls** (surveys, instalações) | US$ 0,50 – 5,00 por conclusão | **É aqui que está o dinheiro.** |
| 4 | **Programa de indicação** | Você não paga: o usuário traz outro | Crescimento sem custo de aquisição. |
| 5 | **Comissões de afiliado** (exchanges, carteiras) | US$ 5 – 50 por cadastro qualificado | Ocasional, mas alto valor. |

### Por que o offer wall é o rei

Uma claim rende ao faucet ~US$ 0,006 em anúncio. Uma única pesquisa respondida no offer wall rende de US$ 0,50 a US$ 5,00 — **de 80 a 800 vezes mais**.

Os faucets que sobrevivem tratam o claim como **isca de retenção** (o motivo do usuário voltar todo dia) e o offer wall como **o produto de verdade**. O claim é o anzol. O offer wall é o peixe.

**Consequência prática para o seu projeto:** o código atual implementa o claim e o jogo. Não implementa shortlink, não implementa offer wall. Ou seja, o projeto tem hoje a parte que dá **menos** dinheiro e falta justo a que dá mais.

---

## 3. A conta que decide tudo (economia unitária)

### 3.1 Quanto você pode pagar por claim

Usando **BTC = US$ 82.000** (referência de outubro/2026) e assumindo **3 impressões de anúncio por claim** (banner topo + banner rodapé + sticky — o mínimo honesto):

| CPM real do seu tráfego | Receita por claim | **Payout máximo sustentável** |
|---|---|---|
| US$ 1,00 (tráfego Brasil ruim) | US$ 0,0030 | **3,66 sats** |
| US$ 2,00 (Brasil bom / misto) | US$ 0,0060 | **7,32 sats** |
| US$ 4,00 (misto com EUA/Europa) | US$ 0,0120 | **14,63 sats** |
| US$ 8,00 (tráfego premium US/UK) | US$ 0,0240 | **29,27 sats** |

Com **5 impressões por claim** (adicionando interstitial e pop-under), os números sobem ~66%: de 6 sats a 49 sats.

### 3.2 O problema do seu projeto hoje

A configuração atual do seu código paga **10 sats por claim, a cada 30 minutos**.

- 10 sats = **US$ 0,0082**
- Para empatar, você precisaria de **CPM de US$ 2,73** com 3 impressões
- A faixa realista de CPM para tráfego majoritariamente brasileiro é **US$ 0,30 a 1,50**

**Resultado: o projeto, como está configurado, perde dinheiro em cada claim.** Você pagaria ~1,8x mais do que recebe. Está no papel a receita certa, mas o número está calibrado para um faucet com 10x o seu tráfego e tráfego dos EUA.

Isso não é erro do Manus — é que ninguém tinha feito essa conta antes de escolher o número. **Este é exatamente o tipo de decisão que só o dono do negócio pode tomar.**

### 3.3 O valor de um usuário ativo

Configuração calibrada para um faucet pequeno: **5 sats por claim, 8 claims por dia** por usuário.

| Item | Valor/dia |
|---|---|
| Payout (5 sats × 8 claims) | −US$ 0,0328 |
| Anúncio (24 impressões @ CPM US$ 1,50) | +US$ 0,0360 |
| Offer wall (3% completam 1 oferta de US$ 1) | +US$ 0,0100 |
| **Líquido por usuário ativo** | **+US$ 0,0132** |

**US$ 0,0132 por dia = US$ 0,40 por mês por usuário ativo.**

Guarde esse número. Toda pergunta de negócio se responde com ele: "vale a pena pagar X reais de anúncio no Instagram?" → Se o custo de adquirir um usuário for maior que US$ 0,40, **não vale**, porque ele nunca se paga.

### 3.4 O risco que ninguém comenta: você paga em BTC e ganha em dólar

Seus custos são em **Bitcoin**. Sua receita é em **dólar**.

| Preço do BTC | Custo de 5 sats/claim | Payout diário com 1.000 DAU |
|---|---|---|
| US$ 82.000 (hoje) | US$ 0,0041 | US$ 32,80 |
| US$ 120.000 | US$ 0,0060 | US$ 48,00 |
| US$ 164.000 (2x) | US$ 0,0082 | **US$ 65,60** |

**Se o Bitcoin dobrar de preço, seu custo de payout dobra e sua receita de anúncio não muda nada.** Você passa de lucro para prejuízo da noite para o dia — sem ter feito nada de errado.

**Requisito obrigatório de software:** o painel administrativo precisa permitir **mudar os sats por claim a qualquer momento, sem tocar em código**. Se você tiver que pedir para alguém alterar o programa toda vez que o BTC oscilar, você está refém. O código atual já tem uma tabela `settings` — ela precisa ser usada de verdade, com um botão na tela do admin.

---

## 4. O inimigo nº 1: fraude

Aqui está a conta que mata faucets novamente. Preste atenção nela.

Um atacante baixa um bot, contrata um serviço de resolução de captcha e roda 1.000 claims:

| Item | Valor |
|---|---|
| Extraído do seu faucet (1.000 × 10 sats) | **US$ 8,20** |
| Custo do captcha-solver (US$ 0,60 por 1.000) | US$ 0,60 |
| **Lucro do atacante** | **US$ 7,60 — retorno de 13,7x** |
| Sua receita de anúncio nesse tráfego | ~US$ 0,00 (redes filtram tráfego de bot) |
| **Seu prejuízo** | **US$ 8,20** |

Escalar para 10.000 claims em uma noite: **US$ 82 de prejuízo direto**, saindo do seu bolso, com receita zero.

Não é hipótese. É rotina. Serviços de solução de captcha anunciam a US$ 0,60 por mil resoluções — e resolvem Turnstile, reCAPTCHA e hCaptcha. **Qualquer captcha é derrotável se o atacante estiver disposto a gastar centavos.**

### As camadas de defesa (na ordem de retorno)

1. **Threshold de saque alto o suficiente para dar tempo de detectar.** Se o usuário só pode sacar com 10.000 sats acumulados, o bot precisa trabalhar por dias antes de levar qualquer coisa — e nesse tempo você o vê. **Esta é a defesa mais eficaz e mais barata que existe.**
2. **Um cadastro por IP / por dispositivo.** Bloqueia o ataque amador.
3. **Captcha invisível.** Cloudflare Turnstile é gratuito e sem limite de requisições. hCaptcha e reCAPTCHA têm nível grátis de 10.000/mês. Não resolve sozinho, mas encarece o ataque.
4. **Rate limit agressivo nas rotas de claim.** O projeto hoje permite 60 requisições por minuto por IP. Para claim, isso deveria ser "1 por intervalo de claim, ponto".
5. **Detecção de padrão.** Mesma carteira de destino em vários cadastros; claims em intervalos exatos de relógio; velocidade impossível para um humano com tela. Um relatório semanal no painel admin resolve.
6. **Cloudflare na frente de tudo (grátis).** Filtra uma camada antes de chegar no seu servidor.
7. **Validação da carteira de destino no FaucetPay.** Uma carteira por usuário; punir reuso.

**Defesa não é opcional.** Sem ela, você não está abrindo um negócio — está abrindo um caixa eletrônico.

---

## 5. Números reais de mercado (outubro/2026)

Tudo abaixo é pesquisado, não estimado.

### Cripto e taxas
- **BTC:** ~US$ 81.300 – 82.700 (10 a 16/out/2026). Fonte: CoinGecko, CoinDesk, Yahoo Finance.
- **Taxa de rede Bitcoin:** **1 a 3 sat/vB** — historicamente baixíssima. Transação típica (~140 vB) custa **140 a 420 sats** (US$ 0,11 a US$ 0,34). Set/2026: mediana de 1 sat/vB em 90% das medições.
- **Implicação:** enviar BTC on-chain está barato agora. Mas você **não vai** pagar on-chain — ver FaucetPay abaixo.

### Anúncios
- **CPM para tráfego cripto:** US$ 2 – 8 (visão otimista de rede).
- **Realidade de publisher:** Coinzilla US$ 1–5 · Bitmedia US$ 0,50–3 · A-ADS US$ 0,30–1,50.
- **A-ADS (Anonymous Ads):** US$ 114.499/mês distribuídos entre 2.079 publishers ativos = **US$ 55/mês para o publisher médio**. Este é o retrato honesto do mercado de cauda longa.
- **Tráfego do Brasil/Ásia/África** paga bem menos que EUA/Europa. É uma desvantagem estrutural sua — seus usuários provavelmente serão brasileiros e indianos.

### Offer walls
- US$ 0,50 – 5,00 por conclusão.
- Exemplo real de economia: oferta de US$ 3,50 de CPI → rede fica 30–40% → publisher recebe **~US$ 1,82 a 2,45 líquido**, menos a taxa de reversão (*scrub*).
- Redes: CPALead, AdGate, OfferToro, Adscend, Theorem Reach, AdWork, Lootably, AyeT, IronSource.

### Benchmarks de faucets
- Faucet novo, primeiro mês: **US$ 50 – 100**.
- Faucet estabelecido com tráfego consistente: **US$ 500 – 1.000/mês** (meta realista).
- Faucet de alto tráfego: US$ 2.000 – 8.000/mês (não é o seu cenário).

### Infraestrutura
- Hospedagem compartilhada: US$ 3–15/mês · VPS: US$ 4–7/mês (Hostinger KVM1 US$ 5,99 · Kamatera desde US$ 4).
- Laravel Cloud: US$ 5/mês + uso. Render: US$ 7/mês. Hostinger VPS: US$ 6,49, renova US$ 11,99.
- **Cloudflare:** grátis (proxy, CDN, WAF, rate limiting).
- **Cloudflare Turnstile:** grátis, sem limite de requisições.
- **Domínio .com:** ~US$ 10–12/ano.

---

## 6. Estrutura de custos

### Fase 1 — Operação mínima (meses 1 a 6)

| Item | Mensal |
|---|---|
| Domínio .com | ~US$ 1 |
| Hospedagem (VPS pequena) | US$ 5 – 7 |
| Cloudflare (proxy + Turnstile + rate limit) | US$ 0 |
| E-mail transacional | US$ 0 |
| FaucetPay (receber = grátis) | US$ 0 |
| SSL/HTTPS | US$ 0 |
| **Subtotal** | **US$ 6 – 8** |

### Capital de giro — o ponto crítico

Este é o item que **derruba** operadores desavisados:

- Você **paga** os usuários agora (FaucetPay é instantâneo).
- Você **recebe** dos anunciantes depois — Bitmedia paga Net-30; Coinzilla paga semanal ou mensal, com mínimo de €50; A-ADS paga diário a partir de 0,001 BTC.

| Usuários ativos/dia | Você desembolsa | Precisa ter em caixa |
|---|---|---|
| 200 | US$ 6,56/dia | ~US$ 300 |
| 500 | US$ 16,40/dia | ~US$ 740 |
| 1.000 | US$ 32,80/dia | ~US$ 1.480 |

Esses valores cobrem ~45 dias de atraso de recebimento. **Sem esse capital de giro, o faucet quebra mesmo sendo lucrativo no papel** — clássico problema de fluxo de caixa.

**Escolha estratégica:** comece pelo **A-ADS**, que paga **diariamente**, sem mínimo de tráfego e sem exigir CNPJ. CPM mais baixo, mas o caixa acompanha. Migre para Coinzilla/Bitmedia quando tiver volume.

**Atenção:** A-ADS exige domínio com **no mínimo 3 meses de idade**. Por isso a primeira tarefa é comprar o domínio **hoje**.

### Fase 2 — A partir do momento em que faturar

| Item | Mensal |
|---|---|
| Contador | R$ 150 – 300 |
| Reserva de segurança (10% da receita) | variável |
| **Subtotal adicional** | ~R$ 200 – 350 |

---

## 7. O que fazer com o jogo de dados (⚠️ leia antes da seção 8)

O seu projeto tem um jogo de dados onde o usuário aposta saldo. **No Brasil, isso é aposta de quota fixa.** É o item que transforma um projeto de hobby em risco jurídico, e é o ponto que nenhuma IA comentou com você até agora.

Tem três saídas:

| Opção | Descrição | Risco legal |
|---|---|---|
| **A. Remover o jogo** | Tira a rota de dados do sistema. O faucet fica sendo só faucet. | Nenhum. **Recomendado.** |
| **B. Jogo grátis sem aposta** | Dados que dão pontos de XP, cosméticos, posição no ranking. Sem apostar saldo, sem apostar cripto. | Baixo, com cuidados. |
| **C. Jogo com prêmio** | Sorteio com prêmio em cripto. | Precisa avaliar distribuição gratuita de prêmios. Consulte advogado. |
| **D. Manter aposta** | Como está hoje. | **Inviável.** Ver seção 8. |

**Recomendação: opção A ou B.** Você já tem conteúdo suficiente para um produto digno sem isso, e a opção D custa R$ 30 milhões para ser legal no Brasil.

---

## 8. Brasil: o que a lei permite

### 8.1 Lei 14.790/2023 — a Lei das Bets

Sancionada em 30/12/2023. Regulamenta **apostas de quota fixa**, incluindo **"eventos virtuais de jogos on-line"** — categoria na qual um jogo de dados com aposta se encaixa perfeitamente.

O que a lei exige de quem opera:

| Requisito | Valor/Exigência |
|---|---|
| **Outorga** | **R$ 30.000.000** por autorização, validade de 5 anos |
| Pessoa jurídica | Constituída com sede e administração no Brasil |
| Capital social mínimo | Comprovação de capacidade econômica e financeira |
| Domínio | Obrigatório `.bet.br` |
| KYC | CPF válido + verificação biométrica ou documental, 18+ |
| Jogo responsável | Autoexclusão, políticas obrigatórias |
| Prevenção à lavagem | Programas de integridade obrigatórios |
| Órgão regulador | SPA — Secretaria de Prêmios e Apostas, Ministério da Fazenda |

**Tradução direta:** a multa por operar sem autorização é severa e a barreira de entrada é de **R$ 30 milhões**. Não é "difícil". É **impossível** para pessoa física com operação pequena.

> ⚠️ **Isto não é aconselhamento jurídico.** Leia a seção como um alerta para buscar um advogado, não como um parecer. A regulamentação vem sendo ajustada por portarias da SPA desde 2024, e as regras de publicidade, tributação e KYC têm mudado.

### 8.2 O que continua permitido

Um faucet **sem aposta** — em que o usuário responde captcha, vê anúncio e recebe uma quantia fixa e simbólica — **não é aposta**. Não há valor em risco com expectativa de prêmio. É ferramenta de recompensa/promocional.

O que você precisa fazer para manter esse enquadramento:
- **Nenhuma** mecânica de aposta: sem apostar saldo, sem "dobrar ou perder", sem roleta com stake.
- **Nenhum** jogo com dinheiro do usuário.
- **Aviso claro** de que os valores são simbólicos e decorrentes de publicidade.
- Se um dia quiser premiação por sorteio, **consulte advogado antes** — distribuição gratuita de prêmios tem regras próprias.

---

## 9. Tributação (pessoa física)

> ⚠️ Os números abaixo são referência de pesquisa. **Você precisa de um contador.** O custo de R$ 150–300/mês é o melhor dinheiro que você vai gastar neste projeto.

### Receita de anúncio (o que entra)
Você é pessoa física recebendo de empresas, muitas delas no exterior (Coinzilla é irlandesa, A-ADS é de Hong Kong, Bitmedia é estoniana). Isso tende a se enquadrar como **rendimento recebido do exterior** — sujeito a tribulação e a obrigações de declaração próprias. Há também a questão de a atividade caracterizar operação comercial, que pede CNPJ.

**Por que isso importa:** sem CNPJ, várias redes (Coinzilla, Bitmedia) podem simplesmente recusar ou não conseguir te pagar. Abrir um MEI ou ME pode ser pré-requisito prático, não só fiscal. Isso reforça a seção 15 — começar pelo A-ADS, que não exige.

### Ganho de capital com cripto
| Situação | Alíquota | Isenção | Apuração |
|---|---|---|---|
| Exchange nacional (CNPJ no Brasil) | 15% a 22,5% (progressiva) | Vendas até R$ 35.000/mês | Mensal, DARF **4600** |
| Exchange estrangeira / sem exchange | 15% fixa | **Não há** | Anual, na declaração |

- A isenção mensal de R$ 35.000 segue valendo em 2026 (a MP 1.303/2025 tentou derrubá-la e caducou em 08/10/2025).
- **DeCripto (IN RFB 2.291/2025):** reporte mensal de operações até o **último dia útil do mês seguinte**. Substituiu a IN 1.888.
- Declaração de posse: ficha **Bens e Direitos, Grupo 08** (código 01 = Bitcoin), pelo **custo de aquisição** — nunca pelo valor de mercado.

### O ponto que quase ninguém vê
Você vai acumular saldo em BTC no FaucetPay (custodial, fora do Brasil). Isso é ativo no exterior. **Não deixe de declarar por achar que é pouco.** Anote desde o primeiro satoshi:

> Planilha: data · quantidade de sats recebida · cotação USD/BRL na data · origem

Fazer isso desde o começo vale mais que qualquer otimização depois.

---

## 10. LGPD

Você vai coletar: endereço de carteira, e-mail, endereço IP, dados de comportamento. **Isso é dado pessoal sob a LGPD (Lei 13.709/2018).** Você não precisa de advogado para fazer o básico — precisa de disciplina:

- [ ] Política de privacidade publicada, em português claro.
- [ ] Coletar o mínimo (hoje o cadastro pede e-mail **opcional** — mantenha assim).
- [ ] Base legal declarada (execução de contrato + legítimo interesse antifraude).
- [ ] Retenção definida: logs de IP por quanto tempo? (sugestão: 90 dias, para antifraude).
- [ ] Canal de contato para exclusão de dados.
- [ ] Se usar Cloudflare, ela processa dados por você — mencione no texto.
- [ ] Não venda nem compartilhe dados. (Faucets que fazem isso viram paria no setor.)

---

## 11. Como conseguir usuários — o verdadeiro gargalo

**Ouça isto com atenção: este é o capítulo que decide o sucesso ou o fracasso do projeto.** Você pode escrever o melhor código do mundo e ter zero usuários.

### O que funciona, em ordem de retorno real

| Canal | Eficácia | Prazo | Custo |
|---|---|---|---|
| **1. SEO / blog de conteúdo** | Altíssima | 6–12 meses | Tempo |
| **2. Diretórios e rotadores de faucet** | Alta, imediata | Dias | Grátis |
| **3. BitcoinTalk + Reddit + fóruns** | Média | Semanas | Grátis |
| **4. Programa de indicação (10–25%)** | Média-alta, composta | Meses | % do payout |
| **5. YouTube / TikTok / Telegram** | Variável | Meses | Tempo |
| **6. Anúncio pago** | **Quase sempre negativa** | — | Dinheiro |

### Por que anúncio pago não funciona para você
Cada usuário vale **US$ 0,40/mês** de lucro. Se você pagar US$ 1,00 por clique para adquirir um usuário, ele precisa ficar **2,5 meses** só para empatar — e a maioria dos usuários de faucet abandona em dias. **Faça a conta antes de gastar um centavo em tráfego.**

### A jogada que realmente funciona
Os "rotadores" e diretórios de faucet são a porta de entrada do setor — eles listam os faucets que pagam mais e com mais confiabilidade, e os usuários passam por ali todos os dias. Você entra nesses diretórios contatando os mantenedores e informando: quanto paga, de quanto em quanto tempo, qual a comissão de indicação, qual o saque mínimo.

**Isso é gratuito, imediato e ignora completamente o SEO.** É a coisa mais eficiente que você pode fazer no mês 1.

### A regra do abandono
Um usuário de faucet que não volta em 3 dias raramente volta. Retenção vale mais que aquisição:
- Bônus de login diário progressivo (dia 2 maior que dia 1, dia 7 com prêmio)
- Streak (dias consecutivos)
- Nível/XP que aumenta os sats por claim
- Notificação de "seu próximo claim está disponível"

Nada disso existe no projeto hoje. Construir isso rende mais que qualquer campanha de marketing.

---

## 12. O plano de 12 meses

### Mês 0 — Decisões e alicerce (semana 1–2)
Sem escrever código. Sem gastar dinheiro além do domínio.

- [ ] **Comprar o domínio HOJE.** A-ADS exige 3 meses de idade do domínio. Cada dia de atraso é um dia a mais sem a única rede que paga diariamente e não exige CNPJ. *(≈ US$ 12/ano — o melhor investimento do projeto)*
- [ ] **Decidir o veredito do jogo de dados** (seção 7). Recomendação: remover a aposta.
- [ ] **Escrever num papel:** qual moeda você vai distribuir. BTC é o chamariz, mas LTC/DOGE/TRX têm apelo e custo menor. FaucetPay suporta várias — começar com 2 ou 3 amplia o público sem custo extra.
- [ ] **Falar com um advogado** (~1 consulta) especificamente sobre: faucet sem aposta, é permitido? Precisa de autorização? Se não puder pagar, pesquise você mesmo — mas registre a dúvida e resolva antes do lançamento.
- [ ] **Falar com um contador** sobre CNPJ/MEI e tributação de receita do exterior.
- [ ] **Criar conta no FaucetPay** e entender a API de pagamento.

**Custo do mês 0: ~US$ 12 (domínio).**

### Mês 1 — Tornar o site real
- [ ] **Corrigir a falha de segurança do admin** (qualquer usuário hoje pode se dar saldo infinito). **Inadiável.** Sem isso o site não pode receber uma única pessoa.
- [ ] Deploy real: backend Laravel + MySQL num servidor, HTTPS, domínio apontado.
- [ ] Cloudflare na frente de tudo. Turnstile no cadastro e no claim.
- [ ] Integração FaucetPay para saque — **isto substitui a integração com blockchain**, que é a parte que você achava que ia ser impossível.
- [ ] Calibrar os sats por claim para um número sustentável (comece em **5 sats**) e criar a tela do admin para ajustar.
- [ ] Saque mínimo alto (10.000 sats) como defesa antifraude.
- [ ] Política de privacidade publicada.

**Custo: ~US$ 18 (domínio proporcional + hospedagem).**

### Mês 2 — Ligar o dinheiro
- [ ] **A-ADS**: cadastrar o site. Paga diário, sem mínimo de tráfego, sem CNPJ.
- [ ] Colocar 3 posições de banner (topo, rodapé, sticky). **Não exagere** — redes banem sites com excesso de anúncio.
- [ ] **Offer wall**: integrar uma rede (AyeT ou CPALead para começar).
- [ ] **Shortlink** antes do claim — testar se o atrito extra vale a receita extra. É reversível.
- [ ] Painel admin: relatório de receita vs. payout, por dia. **Sem isso você opera no escuro.**

**Meta: primeiras receitas. Não espere mais que US$ 30 no primeiro mês.**

### Mês 3 — Lançar
- [ ] **Cadastrar em todos os diretórios e rotadores de faucet.** Esta é a ação de maior impacto de todo o plano.
- [ ] Tópico no BitcoinTalk (seção de micro-earnings).
- [ ] Preparar o programa de indicação para divulgação (o código já tem 10%).
- [ ] Publicar 3–5 páginas de conteúdo SEO (ex.: "o que é faucet", "como sacar do FaucetPay").
- [ ] **Domingo de lançamento:** Telegram, Reddit r/beermoney, r/CryptoFaucets.

**Meta: 100–200 usuários ativos/dia até o fim do mês.**

### Meses 4–6 — Retenção e otimização
- [ ] Bônus diário, streak, XP.
- [ ] Detecção de fraude ativa (relatório semanal).
- [ ] Migrar para Coinzilla/Bitmedia quando houver volume.
- [ ] Contratar contador. Abrir CNPJ se necessário.
- [ ] Otimizar posição de anúncio com base em dado, não em achismo.

**Meta mês 6: 500 usuários ativos/dia · US$ 150–250/mês de receita bruta.**

### Meses 7–12 — Escala ou encerramento
- [ ] Dobrar a produção de conteúdo SEO. É o único canal que compõe.
- [ ] Expandir para 2ª e 3ª moeda.
- [ ] Revisar economia trimestralmente (o BTC muda).
- [ ] **No mês 12, aplicar os critérios de encerramento da seção 14.**

**Meta mês 12: 1.000 usuários ativos/dia · US$ 400/mês líquido.**

---

## 13. Projeções financeiras: 3 cenários

Premissas: BTC US$ 82.000 · 8 claims/usuário/dia · 3 impressões/claim · offer wall a 3% de participação.

### Cenário A — Hobby / portfólio (o mais provável se você trabalhar pouco)

| | Mês 3 | Mês 6 | Mês 12 |
|---|---|---|---|
| Usuários ativos/dia | 50 | 150 | 300 |
| Receita bruta | US$ 15 | US$ 45 | US$ 120 |
| Payout | US$ 8 | US$ 25 | US$ 49 |
| Custos | US$ 8 | US$ 8 | US$ 8 |
| **Lucro líquido** | **−US$ 1** | **+US$ 12** | **+US$ 63** |

**Leitura honesta:** R$ 340/mês no fim do primeiro ano. É um hobby que se paga. **E isso não é fracasso** — você teria um produto real, funcionando, que você construiu. É a base para decidir se vale escalar.

### Cenário B — Renda extra (a meta que faz sentido)

| | Mês 3 | Mês 6 | Mês 12 |
|---|---|---|---|
| Usuários ativos/dia | 100 | 500 | 1.000 |
| Anúncios | US$ 36 | US$ 180 | US$ 360 |
| Shortlink | US$ 12 | US$ 60 | US$ 120 |
| Offer wall | US$ 10 | US$ 50 | US$ 100 |
| **Receita bruta** | **US$ 58** | **US$ 290** | **US$ 580** |
| Payout (5 sats) | US$ 33 | US$ 197 | US$ 393 |
| Custos | US$ 8 | US$ 8 | US$ 8 |
| **Lucro líquido** | **+US$ 17** | **+US$ 85** | **+US$ 179** |

**Leitura:** ~US$ 180/mês (R$ 970) no fim do ano, trabalhando de 8 a 12 horas por semana. Exige os 12 meses do plano executados com disciplina, especialmente na parte de aquisição.

### Cenário C — Negócio real (improvável sozinho, e é bom você saber)

| | Mês 12 | Mês 24 |
|---|---|---|
| Usuários ativos/dia | 5.000 | 20.000 |
| Receita bruta | ~US$ 2.900 | ~US$ 11.600 |
| Custo incluindo equipe parcial | US$ 1.200 | US$ 5.000 |
| **Lucro** | **~US$ 1.700** | **~US$ 6.600** |

Exige equipe, capital de giro de milhares de dólares, presença em várias geos, meses de marketing intenso e guerra antifraude em tempo integral. **Não é o seu cenário hoje.** Está aqui para você saber que o teto existe — e enxergar quando (se) você chegar lá.

---

## 14. Critérios de encerramento (leia antes de começar)

Decida **agora**, com a cabeça fria, quando você vai desligar. Isso evita arrastar um projeto morto por dois anos movido por esperança.

Desligue se, **em qualquer momento**:

- [ ] Os bots consumirem **mais de 30%** do seu payout em dois meses seguidos.
- [ ] Você estiver gastando **mais de 1 hora por dia** em suporte/fraude para uma receita abaixo de US$ 200/mês.
- [ ] Você precisar injetar dinheiro do bolso por mais de 3 meses seguidos.
- [ ] Receber uma notificação legal, da SPA ou de qualquer autoridade sobre o projeto.

Avalie no **mês 12**:

- [ ] Menos de **500 usuários ativos/dia** → feche, ou reduza a operação mínima.
- [ ] Menos de **US$ 150/mês** de receita bruta → o canal de aquisição não está funcionando.
- [ ] Você perdeu o interesse → feche com dignidade e publique no GitHub como portfólio. Isso tem valor real.

**Encerrar não é fracasso. Arrastar por anos é que é.**

---

## 15. Checklist dos próximos 7 dias

Nesta ordem. Nada de código ainda.

| # | Tarefa | Por quê | Custo |
|---|---|---|---|
| 1 | **Comprar o domínio** | A-ADS exige 3 meses de idade. É o gargalo real. | US$ 12/ano |
| 2 | **Decidir o destino do jogo de dados** | Aposta = R$ 30 milhões de outorga. Remover a aposta. | R$ 0 |
| 3 | **Abrir conta no FaucetPay** | É a sua via de pagamento. Sem ela não existe faucet. | R$ 0 |
| 4 | **Consultar um advogado** (1 conversa) | Trocar incerteza jurídica por informação. | variável |
| 5 | **Consultar um contador** | CNPJ? Receita do exterior? Como declarar? | variável |
| 6 | **Anotar num papel**: meta de usuários para 12 meses | Sem número, não há como avaliar. | R$ 0 |
| 7 | **Só então** pedir a correção técnica | Código é consequência da decisão, não o contrário. | — |

---

## 16. Resumo final — a verdade em 10 linhas

1. Faucet é **negócio de mídia**, não de criptomoeda.
2. Você compra atenção com sats e revende com anúncio.
3. Meta é magra: **US$ 0,006 por claim** de receita.
4. **5 sats por claim** é o teto realista para começar. 10 sats dá prejuízo.
5. **1 usuário ativo = ~US$ 0,40/mês.** Para R$ 2.000/mês: ~1.000 pessoas voltando todo dia.
6. **Offer wall é onde está o dinheiro.** O claim é a isca.
7. **Fraude é o inimigo nº 1** e a defesa mais eficaz é um saque mínimo alto.
8. **O jogo de aposta precisa sair por causa da Lei 14.790/2023** (R$ 30 milhões de outorga).
9. **Conquistar usuários é mais difícil que programar.** Sem 1.000 DAU, o plano é lindo e vazio.
10. **Custos são baixos (US$ 8/mês). O que é caro é o seu tempo, e ele é finito.**

**O caminho que faz sentido:** construir, lançar em 3 meses, operar por 12, avaliar com número na mão. Se chegar a US$ 150–200/mês líquido, você terá algo raro — um negócio real, construído por você, que quase ninguém consegue tirar do papel. Se não chegar, você terá aprendido programação, produto, marketing e operação, com US$ 100 de prejuízo. **Os dois resultados são bons negócios.**

O ruim é o terceiro caminho: nunca decidir, nunca lançar e nunca encerrar. É o que faz uma pasta chamada "faucet" ficar parada por dois anos no seu GitHub.

---

## 17. Estudo de caso: a queda do FreeBitco.in (atualizado em 17/10/2026)

> Este capítulo foi escrito depois da notícia: **o maior faucet do mundo, com 12 anos de operação, saiu do ar.** Testei agora, em 17/10/2026: `freebitco.in` responde **502 Bad Gateway** — o Cloudflare está de pé, o servidor de origem não. Não é manutenção de alguns minutos: o site está assim há semanas.

### 17.1 A linha do tempo (o que se pode verificar)

| Data | Fato |
|---|---|
| Nov/2024 | Regulador holandês (KSA) multa a operadora **FBC B.V.** em **€ 280 mil por semana**, teto de **€ 840 mil**, por oferecer jogos de azar (Hi-Lo, loteria) sem licença local [1](https://thebitcoinmanual.com/articles/the-fall-of-freebitco-in/) |
| Dez/2024 | Regulador australiano (ACMA) envia advertência formal à mesma empresa por jogo ilegal [7](https://www.publish0x.com/pennypay/the-end-of-freebitcoin-why-the-famous-faucet-stopped-paying-xnjxnpl) |
| Ago–Set/2025 | Saques começam a travar em "pending", sem txid e sem resposta do suporte [1](https://thebitcoinmanual.com/articles/the-fall-of-freebitco-in/) |
| 10/Out/2025 | **O domínio expira e o site sai do ar.** Renovado em seguida, mas a confiança já foi [10](https://www.reddit.com/r/PennyPay/comments/1o3u8gz/freebitcoin_site_down_domain_expired_oct_2025/) |
| Nov/2025 | Pontos de recompensa cortados pela metade sem aviso; suporte desaparece [1](https://thebitcoinmanual.com/articles/the-fall-of-freebitco-in/) |
| 12/Dez/2025 | **Comunicado oficial de encerramento**, com a justificativa de "uso indevido generalizado" e a promessa de liberar saques "no início de 2026" [4](https://best-faucets.com/freebitcoin-the-largest-faucet-has-closed-and-gone-offline) |
| Fev/2026 | Usuários relatam site inacessível e login quebrado; sessões invalidadas [2](https://www.weex.com/learn/articles/freebitcoin-has-shut-down-what-users-should-know-fvjnx0k7bf8mgqzr64kigj9k) |
| 16/Mai/2026 | Botões de **depósito** voltam a funcionar — enquanto saques seguem travados. É o que faz a comunidade chamar de **exit scam** [5](https://freebitcoinscam.com/the-fake-shutdown-in-2026/) |
| Jun/2026 | Diretórios de faucet **deslistam** a plataforma após falha no teste de saque [9](https://freecrypto.world/blog/freebitco-in-stopped-paying) |
| Ago–Out/2026 | **502 Bad Gateway** permanente [15](https://www.trustpilot.com/review/freebitco.in) |

### 17.2 O detalhe que dói: tráfego não é negócio

Em agosto de 2026, o FreeBitco.in ainda tinha cerca de **3,8 milhões de visitas por mês**, com Brasil entre os principais países [2](https://www.weex.com/learn/articles/freebitcoin-has-shut-down-what-users-should-know-fvjnx0k7bf8mgqzr64kigj9k). Visitantes não pagam boleto. O que mede a saúde de um faucet é uma coisa só: **o saque sai?** Pegue essa régua para o seu projeto.

### 17.3 As 3 causas — e nenhuma delas é de código

**1. Fraude.** A justificativa oficial foi "abuso generalizado". Um site que paga por clique atrai exércitos de bots, e bot não vê anúncio: consome sats e não gera receita. É exatamente a seção 4 deste plano. O FreeBitco.in tinha escala, equipe e 12 anos de dados — e mesmo assim perdeu essa guerra. **Você, sozinho e com orçamento zero, precisa de uma defesa que funcione dormindo** (saque mínimo alto, 1 conta por IP, Turnstile, limite por rota).

**2. Regulação — e aqui está o aviso mais caro deste documento.** A multa holandesa não foi por causa do faucet. Foi por causa do **jogo de azar** (Hi-Lo, loteria, multiplicador). O FreeBitco.in era as duas coisas, e foi o braço de aposta que trouxe o regulador para dentro da casa. Seu projeto tem um **jogo de dados com aposta de saldo** — é o mesmo desenho, em escala minúscula. A diferença é que você está no Brasil, onde a outorga de apostas de quota fixa chega a **R$ 30 milhões** (seção 8). **A decisão da seção 7 acabou de ficar óbvia: tire a aposta, mantenha o dado como diversão sem saldo.**

**3. Custódia e confiança.** O FreeBitco.in também aceitava **depósitos** e pagava juros. No momento em que o caixa apertou, ele estava segurando dinheiro dos outros — e virou suspeito de sumir com ele. **Regra para o seu projeto: nunca aceite depósito.** Faucet paga para fora, só. Sem depósito, sem "saldo rendendo", sem função de banco. Isso elimina inteiramente a chance de você ser acusado de golpe — e elimina também a pior dor de cabeça regulatória.

### 17.4 O que muda no seu plano

| O que aconteceu | Consequência prática para você |
|---|---|
| O líder do nicho morreu | **Oportunidade:** milhões de usuários órfãos procurando alternativa. Diretórios e fóruns vão reescrever suas listas agora. |
| A palavra "faucet" foi associada a calote | **Custo:** desconfiança. Prova de pagamento pública e saque mínimo baixo deixam de ser detalhes — são o seu marketing. |
| O braço de aposta causou a multa | **Ação:** remover a aposta do dados. Não é opinião, é precedente. |
| O domínio expirou e derrubou o site | **Ação:** registrar o domínio com renovação automática e deixar o vencimento anotado. Custa R$ 40/ano e já derrubou um gigante. |
| Saque travado matou a reputação | **Ação:** nunca prometer prazo que você não controla; e nunca segurar saldo grande de usuário — limite de saque baixo e processamento automático. |

### 17.5 A verdade final sobre esse caso

O FreeBitco.in não caiu porque o código era ruim. Caiu porque **fraude corroeu a margem, regulação corroeu a operação e a custódia corroeu a confiança**. As três coisas são exatamente as que você disse que não se resolvem com código.

Um gigante de 12 anos caiu por elas. A única vantagem que você tem sobre ele é poder **decidir agora**, antes de ter usuários, que tipo de negócio não quer ser.

---

## 18. Fontes consultadas (16/10/2026, atualizado 17/10/2026)

**Mercado e economia de faucet**
- https://readyfaucet.com/blog/how-to-make-money-with-crypto-faucets — modelo de negócio, US$ 50–100 primeiro mês, meta US$ 500–1.000
- https://www.bitget.com/academy/crypto-faucets-guide — CPM, offer wall, benchmark de alto tráfego
- https://cryptoearnlab.com/compare/best-crypto-faucets/ — economia real por hora
- https://www.revadoo.com/blog/best-crypto-faucet-websites-that-actually-pay-in-2026 — FaucetPay, taxa de rede

**Anúncios**
- https://adbench.io/a-ads-vs-coinzilla/ — A-ADS US$ 114.499/mês ÷ 2.079 publishers, pagamento diário, exigência de domínio
- https://www.blockchain-ads.com/post/coinzilla-review — CPM Coinzilla
- https://bitmedia.io/blog/top-ad-networks — mínimos Bitmedia
- https://flexe.io/blog/crypto-ads-guide/ — benchmarks de CPM por rede
- https://adsnetwork.io/best-cpm-ad-networks-for-publishers-in-2026/ — CPM por nicho e geo

**Offer walls**
- https://www.revboost.com/best/offerwall-networks-for-rewards-sites — redes e taxas
- https://coinis.com/glossary/offer-wall — exemplo real: US$ 1,82 líquido por conversão

**Cripto e taxas**
- https://www.coingecko.com/en/coins/bitcoin — BTC ~US$ 81.300
- https://bitcoinfee.net/ — 1,1–2,2 sat/vB, custo por transação
- https://cryptoticker.io/en/bitcoin-network-fee-utxo-consolidation/ — mediana 1 sat/vB

**FaucetPay**
- https://earnbit.net/faucetpay-review-the-microwallet-that-makes-faucet-earnings-possible/ — taxas, custódia
- https://www.revadoo.com/blog/how-faucetpay-works — transferências internas sem taxa

**Antifraude**
- https://tech-insider.org/cloudflare-turnstile-setup-2026/ — Turnstile grátis; solvers a US$ 0,60/1.000
- https://prosopo.io/blog/best-captcha/ — comparativo e limites

**Hospedagem**
- https://fly.io/learn/laravel-hosting/ — preços de plataformas Laravel
- https://hostadvice.com/vps/brazil/ — VPS no Brasil

**Legal — Brasil**
- https://www.normaslegais.com.br/legislacao/lei-14790-2023.htm — texto da Lei 14.790/2023
- https://nextmag.com.br/regulamentacao-de-apostas-no-brasil-lei-14-790/ — outorga R$ 30 mi, `.bet.br`, KYC, SPA
- https://certiface.io/artigo/lei-das-bets/ — cronograma e obrigações

**Tributação**
- https://blueconsult.com.br/imposto-criptomoedas-brasil-2026/ — regime nacional vs. exterior
- https://www.mattosfilho.com.br/unico/decripto-receita-federal-ocde/ — DeCripto, IN 2.291/2025
- https://panoramacrypto.transfero.com/como-declarar-criptomoedas-no-imposto-de-renda-2026/ — isenção R$ 35 mil

**O caso FreeBitco.in (capítulo 17)** — verificado em 17/10/2026
- https://thebitcoinmanual.com/articles/the-fall-of-freebitco-in/ — linha do tempo completa, multas da KSA, crise de saques
- https://best-faucets.com/freebitcoin-the-largest-faucet-has-closed-and-gone-offline — texto integral do comunicado de encerramento
- https://www.weex.com/learn/articles/freebitcoin-has-shut-down-what-users-should-know-fvjnx0k7bf8mgqzr64kigj9k — KSA/FBC B.V., 3,8 mi de visitas/mês em ago/2026
- https://www.publish0x.com/pennypay/the-end-of-freebitcoin-why-the-famous-faucet-stopped-paying-xnjxnpl — expiração do domínio, ACMA
- https://freebitcoinscam.com/the-fake-shutdown-in-2026/ — reabertura dos depósitos em 16/mai/2026
- https://freecrypto.world/blog/freebitco-in-stopped-paying — deslistagem por falha no teste de saque (jun/2026)
- https://www.trustpilot.com/review/freebitco.in — relatos de usuários e erro 502 (ago–set/2026)
- https://portaldobitcoin.uol.com.br/site-freebitcoin-pioneiro-do-bitcoin-vai-encerrar-atividades/ — cobertura em português

---

*Documento de trabalho. Números de mercado são referências datadas — reconfira antes de tomar decisão financeira. As seções 7, 8 e 9 não substituem advogado e contador. Atualizado em 17/10/2026 com o capítulo 17 (estudo de caso FreeBitco.in).*
