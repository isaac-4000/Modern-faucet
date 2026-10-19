# Plano de Negócio — Faucet de Criptomoedas

**Para:** Isaac (`isaac-4000`)
**Data:** 16 de outubro de 2026 (revisão geral em 18/10/2026 — ver seções 3 e 4: mudança para DGB, pontos atrelados ao dólar, torneira; e capítulo 17: a queda do FreeBitco.in)
**Base:** números reais de mercado pesquisados nesta data. Fontes no final.
**Pré-requisito de leitura:** este plano assume que você leu — e aceitou — o diagnóstico técnico do projeto.

---

## 1. Veredito em uma página

**Um faucet é um negócio de mídia. Não é um negócio de criptomoeda.**

Isso muda tudo. Você não está vendendo Bitcoin — você está comprando atenção barata com Bitcoin e revendendo essa atenção para anunciantes a um preço maior. A criptomoeda é o **custo de aquisição de tráfego**, não o produto. Quem entende isso desde o dia 1 tem chance. Quem pensa que está "distribuindo cripto" quebra no terceiro mês.

**A conta que define se você ganha ou perde dinheiro:**

```
Receita por claim  =  impressões de anúncio × CPM ÷ 1000
Custo por claim    =  pontos pagos ao usuário × US$ 0,00001

Se a primeira linha for maior que a segunda, você tem negócio.
Se for menor, você está pagando para trabalhar.
```

**O número que você precisa decorar: 1 usuário ativo por dia vale cerca de US$ 0,60 por mês de lucro líquido.**

Não US$ 6. Não US$ 60. **Sessenta centavos de dólar por mês.** (Era US$ 0,40 na versão em BTC — ver seção 3.1.)

| Você quer faturar… | …então precisa de |
|---|---|
| US$ 100/mês (R$ 540) | ~165 usuários ativos/dia |
| US$ 400/mês (R$ 2.160) | ~660 usuários ativos/dia |
| 1 salário mínimo (~US$ 282) | ~465 usuários ativos/dia |
| US$ 2.000/mês (R$ 10.800) | ~3.300 usuários ativos/dia |

> **Atualização de 18/10/2026:** o projeto passa a pagar em **DGB**, com unidade interna em **pontos atrelados ao dólar**, ritmo de 4 minutos e teto diário de gasto. A moeda e os números mudaram — a lógica do negócio não. Detalhes na seção 3.

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

> **Revisão de 18/10/2026.** Esta seção foi reescrita depois de uma pesquisa de campo nos faucets que existem hoje. A conclusão mudou a moeda do projeto — e com ela, toda a matemática. A versão anterior, em BTC, está resumida em 3.1 para você entender o que mudou e por quê.

### 3.1 O que a versão anterior dizia — e onde ela errava

O plano original foi escrito com **BTC a US$ 82.000** e payout de 5 sats por claim. A conta fechava no limite:

| CPM do seu tráfego | Payout máximo sustentável (3 impressões) |
|---|---|
| US$ 1,00 | 3,66 sats |
| US$ 2,00 | 7,32 sats |

Com CPM brasileiro de US$ 0,30 a 1,50, **5 sats ficava no fio da navalha** — e o próprio plano avisava disso.

O erro não estava na conta. Estava em **não ter verificado o que o faucet obriga o usuário a acumular antes de sacar**. Era aqui que o BTC matava o modelo:

| | BTC (FaucetPay) | DGB (FaucetPay) |
|---|---|---|
| Taxa de saque | 1.000 sats (**US$ 0,81**) | 0,25 DGB (**US$ 0,00087**) |
| **Mínimo para sacar** | **120.000 sats (US$ 97,76)** | **25 DGB (US$ 0,087)** |
| Claims até o mínimo* | **24.440** | **22** |

\* considerando payout de US$ 0,004 por claim.

**24.440 claims contra 22.** É isso que a frase "taxas mais altas tornaram inviável a manutenção de uma faucet tradicional" significa. Não é a taxa — é o piso. Um usuário de faucet em BTC precisaria de mais de um ano reclamando todo dia pra ver a cor do dinheiro.

### 3.2 Pesquisa de campo: o que os faucets de DGB pagam de verdade

Referências coletadas em 17–18/10/2026. Cotações do dia: **BTC US$ 81.468 · DGB US$ 0,00349 · 1 sat = 0,2334 DGB**.

| Faucet | Payout | Intervalo | **DGB/hora** | US$/hora |
|---|---|---|---|---|
| FaucetPepe | 0,04324 DGB | 10 s (teto 1.000/dia) | **15,56** | US$ 0,0543 |
| **Viefaucet** (2 mi de usuários) | 65 tokens (US$ 0,00065) | 4 min | **2,82** | US$ 0,00975 |
| TopFaucet | 0,14 DGB | 5 min | **1,68** | US$ 0,00586 |
| Faucets menores | 0,00628 a 0,025 DGB | 5 min | 0,075 a 0,30 | — |
| *FreeBitco.in (taxa final, 2025)* | *2 sats* | *1 h* | *0,467* | *US$ 0,00163* |

**Três conclusões que nenhuma planilha te daria:**

1. **O melhor faucet de DGB da lista tem ~4 usuários.** A página pública de "pagamentos recentes" do TopFaucet mostrava 10 saques em 8h44, de 4 carteiras distintas. Ele paga 3,6x mais que a média e é um deserto. **Ser o que mais paga não compra usuário — descoberta compra.**
2. **O que paga 33x mais é uma máquina, não um faucet.** FaucetPepe libera um claim a cada 10 segundos com teto de 1.000 por dia. Nenhum humano clica 1.000 vezes por dia: o teto existe porque, sem ele, o site seria drenado em horas. Não é referência, é alarme.
3. **A taxa do FreeBitco.in ainda é a melhor âncora psicológica disponível.** 2 sats/hora foi o número que milhões de pessoas aceitaram durante uma década. É uma régua testada pelo mercado — e é exatamente onde a sua configuração vai ficar.

### 3.3 A configuração escolhida

```
Moeda:              DGB (DigiByte)
Ritmo:              Um claim a cada 4 minutos (15 por hora)
Payout:             0,03113 DGB por claim  =  US$ 0,000109
Taxa horária:       0,467 DGB/hora         =  US$ 0,00163
                    ↑ exatamente a taxa horária do FreeBitco.in
Mínimo interno:     500 pontos (US$ 0,005) =  1,43 DGB
Teto diário:        R$ 20 por dia (a "torneira")
```

Comparada ao mercado:

| | DGB/hora | Você é |
|---|---|---|
| FaucetPepe | 15,56 | 1/33 |
| Viefaucet | 2,82 | **1/6** |
| TopFaucet | 1,68 | 1/3,6 |
| **Você** | **0,467** | — |

**Você começa pagando 1/6 do melhor faucet do mundo.** Não por escolha — por matemática. Veja 3.6.

### 3.4 A unidade interna: ponto atrelado ao dólar

O Viefaucet tem um token próprio onde **1 token = US$ 0,00001**, fixo. É a ideia mais inteligente daquele site e você vai copiar:

```
1 ponto = US$ 0,00001  (nunca muda)
Claim   = 11 pontos    (= US$ 0,00011 = 0,03113 DGB na cotação de hoje)
Mínimo  = 500 pontos   (= US$ 0,005 = 1,43 DGB)
```

**O que isso resolve:**

- **Seu custo fica 100% em dólar.** O DGB pode subir ou cair: seu custo por claim não muda. É o fim do risco de volatilidade que a versão anterior deste plano chamava de "o risco que ninguém comenta".
- **O usuário vê número redondo.** "500 pontos" em vez de "0,00000005". É a mesma mágica que faz o Viefaucet parecer generoso.
- **Você pode trocar de moeda depois sem mexer em nada.** O ponto é a unidade de verdade; DGB é só a forma de pagar.

**Uma regra, e é importante:** o ponto **nunca** pode ser comprado com dinheiro. Só ganho. Programa de fidelidade em que o usuário acumula pontos é tranquilo; no momento em que ele pode comprar pontos, o instrumento muda de natureza jurídica. **Nunca venda pontos.**

### 3.5 A torneira: teto diário de pagamento

O Viefaucet mostra o saldo da moeda como uma barra que vai drenando conforme os saques acontecem, até esvaziar — e então enchem de novo. Parece gamificação. **É controle de custo.**

E você não precisa construir isso do zero: **a API v2 da FaucetPay tem um limite diário de pagamento em dólar configurado na própria chave.** Se o teto é atingido, a API devolve erro 429 e o pagamento simplesmente não sai [1](https://faucetpay.io/api-docs).

| Teto diário | Payouts de 500 pontos possíveis | Sua perda máxima por dia |
|---|---|---|
| R$ 10 | 925 | R$ 10 |
| **R$ 20** | **1.851** | **R$ 20** |
| R$ 50 | 4.629 | R$ 50 |

**Não importa quantos bots apareçam: você não consegue perder mais que o teto.** É a resposta mais limpa para a pergunta que a seção 4 deixava em aberto desde a primeira versão deste plano.

A API também entrega, de graça, duas coisas que valem ouro: **chave de idempotência** (se o servidor tentar de novo, nunca paga duas vezes) e **campo de IP do destinatário** para cruzar com seu antifraude [1](https://faucetpay.io/api-docs).

### 3.6 A escada de payout — como você chega no Viefaucet

Você está 6x abaixo do melhor faucet do mundo. Dá para chegar, mas com **gatilho medido**, não no olho.

**A regra:** só suba o payout quando a **receita medida por claim** for pelo menos **3 vezes** o payout atual. Assim o aumento se paga sozinho.

| Fase | Receita medida por claim | Payout | Pontos | DGB/hora |
|---|---|---|---|---|
| **Lançamento** | US$ 0,00012 – 0,00018 | 0,031 DGB | 11 | 0,47 |
| ~500 DAU | US$ 0,00035+ | 0,062 DGB | 22 | 0,93 |
| ~2.000 DAU | US$ 0,00070+ | 0,125 DGB | 44 | 1,87 |
| ~4.000 DAU | US$ 0,00130+ | 0,25 DGB | 88 | **3,73** ← passa o Viefaucet |

**E tem um motivo para começar baixo que não é só dinheiro:** você sempre pode aumentar; **você nunca pode reduzir.** Cortar pagamento destrói confiança de um jeito que não tem volta — foi exatamente isso que matou o FreeBitco.in em novembro de 2025, quando reduziram os pontos pela metade sem avisar ninguém.

### 3.7 O valor de um usuário ativo (recalculado)

Usuário engajado: **25 claims/dia**, 3 impressões por claim, taxa de efetividade de 40% (site novo, adblock, frequency cap).

| Item | Por mês |
|---|---|
| **Custo** — 750 claims × 0,03113 DGB | −US$ 0,082 |
| **Anúncios** — 750 × 3 = 2.250 impressões brutas → 900 efetivas @ CPM US$ 0,60 | +US$ 0,540 |
| **Offer wall** — participação conservadora | +US$ 0,150 |
| **Líquido por usuário ativo** | **+US$ 0,61** |

**1 usuário ativo por dia = ~US$ 0,60 por mês.** (A versão em BTC dava US$ 0,40 — a folga de CPM do DGB comprou 50% a mais.)

| Você quer faturar… | …precisa de |
|---|---|
| US$ 100/mês (R$ 540) | ~165 usuários ativos/dia |
| US$ 400/mês (R$ 2.160) | ~660 usuários ativos/dia |
| 1 salário mínimo (~US$ 282) | ~465 usuários ativos/dia |
| US$ 2.000/mês (R$ 10.800) | ~3.300 usuários ativos/dia |

**Custo de payout com 1.000 DAU:** 750.000 claims/mês × 0,03113 DGB = 23.347 DGB = **US$ 81/mês ≈ R$ 440/mês.**

### 3.8 Por que a escolha do intervalo é uma decisão de segurança

Com intervalo de 4 minutos, uma conta consegue no máximo 360 claims por dia:

```
360 × 0,03113 DGB = 11,2 DGB/dia = US$ 0,039 por conta por dia
```

**O máximo absoluto que uma única conta consegue extrair de você é 4 centavos de dólar por dia.** O intervalo é o seu melhor antifraude — muito melhor do que o limite de saque, porque ele limita a *velocidade*, não só o *destino*.

O FaucetPepe errou exatamente aqui: intervalo de 10 segundos libera 8.640 claims/dia por conta (336 DGB), e foi por isso que precisaram inventar um teto de 1.000 — um remendo para uma decisão errada.

### 3.9 Requisitos de software que esta seção cria

1. **Unidade interna em pontos, não em DGB.** O banco guarda pontos; a conversão para DGB acontece só na hora do saque.
2. **Taxa de conversão editável no painel admin**, sem tocar em código. Você vai usar toda semana.
3. **Intervalo e payout editáveis no painel admin** (a tabela `settings` já existe no projeto — precisa ser usada de verdade).
4. **Teto diário em reais**, aplicado antes de chamar a API da FaucetPay, com a barra da torneira visível para o usuário.
5. **Chave de idempotência** em todo pagamento, e **IP do usuário** enviado em toda chamada de saque.
6. **A moeda não pode estar cravada no código.** Se o DGB der problema, trocar para LTC tem que ser uma linha de configuração.

---
---

## 4. O inimigo nº 1: fraude

> **Revisão de 18/10/2026.** A mudança para DGB e a descoberta do teto diário da FaucetPay mudaram esta seção. O ataque continua existindo — mas agora existe um limite matemático para o dano.

### 4.1 A conta do ataque, refeita com a nova configuração

Um atacante baixa um bot, contrata um serviço de resolução de captcha e roda **1.000 claims** na configuração nova (0,03113 DGB = US$ 0,000109 por claim):

| Item | Antes (10 sats) | Agora (0,03113 DGB) |
|---|---|---|
| Extraído do seu faucet | US$ 8,20 | **US$ 0,11** |
| Custo do captcha-solver (US$ 0,60 / 1.000) | US$ 0,60 | US$ 0,60 |
| **Lucro do atacante** | **US$ 7,60 (13,7x)** | **−US$ 0,49 — prejuízo** |
| Sua receita de anúncio nesse tráfego | ~US$ 0,00 | ~US$ 0,00 |
| **Seu prejuízo** | **US$ 8,20** | **US$ 0,11** |

**Essa é a mudança mais importante de toda esta revisão.** No payout antigo, o ataque dava 13,7x de retorno para o atacante — era um negócio melhor que o seu. No payout novo, **mil claims roubados valem menos que o captcha que o atacante pagou para resolvê-los.** O ataque deixou de ser lucrativo antes de qualquer defesa.

Escalar para 10.000 claims em uma noite: **US$ 1,09 de prejuízo**, contra US$ 82 na configuração antiga.

Não é hipótese. É rotina. Serviços de solução de captcha anunciam a US$ 0,60 por mil resoluções e resolvem Turnstile, reCAPTCHA e hCaptcha. **Qualquer captcha é derrotável se o atacante estiver disposto a gastar centavos — a questão é se o que ele leva vale os centavos.**

### 4.2 A defesa nº 1 agora é outra: o teto diário

A versão anterior deste plano dizia que a defesa mais eficaz era um saque mínimo alto. **Com o DGB isso mudou**, por dois motivos: o saque mínimo pode (e deve) ser baixo, e a FaucetPay passou a oferecer algo melhor.

**A API v2 da FaucetPay aceita um limite diário de pagamento em dólar configurado na própria chave.** Atingido o teto, ela devolve erro 429 e o pagamento não sai [1](https://faucetpay.io/api-docs).

```
Teto de R$ 20/dia  →  não existe cenário em que você perca mais que R$ 20 por dia.
                      Nem com 100 mil bots. Nem dormindo.
```

Isso transforma fraude de **risco existencial** em **risco contabilizável**. Você decide o número no domingo, e o número é lei.

### 4.3 As camadas de defesa (na ordem de retorno)

1. **Teto diário de pagamento (a torneira).** Limite em reais na chave da API. **É a única defesa que limita o dano independentemente de tudo falhar.** Configure no dia 1.
2. **Intervalo de 4 minutos entre claims.** Limita a *velocidade* de extração a ~US$ 0,039 por conta por dia. Mais eficaz que aumentar o saque mínimo, e não custa nada em experiência do usuário.
3. **Um cadastro por IP / por dispositivo.** Bloqueia o ataque amador.
4. **Captcha invisível.** Cloudflare Turnstile é gratuito e sem limite de requisições. hCaptcha e reCAPTCHA têm nível grátis de 10.000/mês. Não resolve sozinho, mas encarece o ataque — e no payout novo, encarecer já é suficiente para matar o incentivo.
5. **Rate limit agressivo nas rotas de claim.** O projeto hoje permite 60 requisições por minuto por IP. Para claim, isso deveria ser "1 por intervalo de claim, ponto".
6. **Detecção de padrão.** Mesma carteira de destino em vários cadastros; claims em intervalos exatos de relógio; velocidade impossível para um humano com tela. Um relatório semanal no painel admin resolve.
7. **Cloudflare na frente de tudo (grátis).** Filtra uma camada antes de chegar no seu servidor.
8. **Validação da carteira de destino no FaucetPay.** Uma carteira por usuário; punir reuso.

**Defesa não é opcional.** Sem ela, você não está abrindo um negócio — está abrindo um caixa eletrônico. Mas note a diferença desta revisão: **a ordem mudou.** Antes você dependia de detectar o bot a tempo. Agora você depende de um número que você mesmo escolheu, e que funciona mesmo se você estiver dormindo.

---
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
- [x] **Moeda definida: DGB.** Decisão tomada em 18/10/2026 com base na pesquisa de campo da seção 3.2. Motivo: mínimo de saque de US$ 0,087 contra US$ 97,76 do BTC. **Plano B registrado:** se o DGB perder liquidez ou for deslistado, migrar para LTC (o código não pode ter a moeda cravada — seção 3.9).
- [ ] **Falar com um advogado** (~1 consulta) especificamente sobre: faucet sem aposta, é permitido? Precisa de autorização? Se não puder pagar, pesquise você mesmo — mas registre a dúvida e resolva antes do lançamento.
- [ ] **Falar com um contador** sobre CNPJ/MEI e tributação de receita do exterior.
- [ ] **Criar conta no FaucetPay** e entender a API de pagamento.

**Custo do mês 0: ~US$ 12 (domínio).**

### Mês 1 — Tornar o site real
- [ ] **Corrigir a falha de segurança do admin** (qualquer usuário hoje pode se dar saldo infinito). **Inadiável.** Sem isso o site não pode receber uma única pessoa.
- [ ] Deploy real: backend Laravel + MySQL num servidor, HTTPS, domínio apontado.
- [ ] Cloudflare na frente de tudo. Turnstile no cadastro e no claim.
- [ ] Integração FaucetPay para saque — **isto substitui a integração com blockchain**, que é a parte que você achava que ia ser impossível. Já criar a chave de API **com teto diário em reais** (seção 4.2).
- [ ] Implementar a **unidade interna em pontos** (1 ponto = US$ 0,00001) e a conversão para DGB na hora do saque. Payout inicial: **11 pontos**.
- [ ] Configurar **0,03113 DGB por claim a cada 4 minutos** e criar a tela do admin para ajustar payout, intervalo e taxa de conversão sem tocar em código.
- [ ] **Barra da torneira** na home mostrando o teto diário drenando.
- [ ] Mínimo de saque de **500 pontos (US$ 0,005)** — o intervalo de 4 minutos é a defesa principal; o teto diário é a rede de segurança.
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

> **Revisão de 18/10/2026.** Todos os números refeitos com a configuração DGB. Premissas na caixa abaixo — se elas estiverem erradas, os números estão errados.

**Premissas desta projeção**

| Premissa | Valor | O que pode derrubar |
|---|---|---|
| Payout | 0,03113 DGB (US$ 0,000109) | — (é você quem controla) |
| Claims por usuário ativo por dia | 25 | Se for 10, corte o líquido pela metade |
| Impressões por claim | 3 | Depende do layout |
| Efetividade das impressões | 40% | Adblock + frequency cap; pode cair a 25% |
| CPM cenário B | US$ 0,60 | Tráfego novo começa em US$ 0,30 |
| Offer wall por usuário/mês | US$ 0,15 (B) / US$ 0,08 (A) | Depende da oferta disponível |

### Cenário A — Hobby / portfólio (o mais provável se você trabalhar pouco)

CPM US$ 0,40 · offer wall fraco · **líquido ≈ US$ 0,36 por DAU/mês**

| | Mês 3 | Mês 6 | Mês 12 |
|---|---|---|---|
| Usuários ativos/dia | 50 | 150 | 300 |
| Receita bruta | US$ 22 | US$ 66 | US$ 132 |
| Payout | US$ 4 | US$ 12 | US$ 25 |
| Custos | US$ 8 | US$ 8 | US$ 8 |
| **Lucro líquido** | **+US$ 10** | **+US$ 46** | **+US$ 99** |

**Leitura honesta:** R$ 535/mês no fim do primeiro ano. É um hobby que se paga. **E isso não é fracasso** — você teria um produto real, funcionando, que você construiu. É a base para decidir se vale escalar.

### Cenário B — Renda extra (a meta que faz sentido)

CPM US$ 0,60 · offer wall ativo · **líquido ≈ US$ 0,61 por DAU/mês**

| | Mês 3 | Mês 6 | Mês 12 |
|---|---|---|---|
| Usuários ativos/dia | 100 | 500 | 1.000 |
| Anúncios | US$ 54 | US$ 270 | US$ 540 |
| Offer wall | US$ 15 | US$ 75 | US$ 150 |
| **Receita bruta** | **US$ 69** | **US$ 345** | **US$ 690** |
| Payout (0,03113 DGB) | US$ 8 | US$ 41 | US$ 82 |
| Custos | US$ 8 | US$ 8 | US$ 8 |
| **Lucro líquido** | **+US$ 53** | **+US$ 296** | **+US$ 600** |

**Leitura:** ~US$ 600/mês (R$ 3.240) no fim do ano, trabalhando de 8 a 12 horas por semana. Exige os 12 meses do plano executados com disciplina, especialmente na parte de aquisição.

**Por que este número é ~3,3x melhor que o da versão em BTC?** Não é mágica: o payout caiu de US$ 393 para US$ 82 por mês em 1.000 DAU. A receita é praticamente a mesma. **Toda a diferença é o custo da moeda.**

### Cenário C — Negócio real (improvável sozinho, e é bom você saber)

| | Mês 12 | Mês 24 |
|---|---|---|
| Usuários ativos/dia | 5.000 | 20.000 |
| Receita bruta | ~US$ 3.450 | ~US$ 13.800 |
| Payout | US$ 410 | US$ 1.640 |
| Custo incluindo equipe parcial | US$ 1.200 | US$ 5.000 |
| **Lucro** | **~US$ 1.840** | **~US$ 7.160** |

Exige equipe, capital de giro de milhares de dólares, presença em várias geos, meses de marketing intenso e guerra antifraude em tempo integral. **Não é o seu cenário hoje.** Está aqui para você saber que o teto existe — e enxergar quando (se) você chegar lá.

### Teste de sensibilidade (faça este teste antes de acreditar em qualquer número acima)

| Se a efetividade das impressões for… | …e o CPM for… | Líquido por DAU/mês |
|---|---|---|
| 40% | US$ 0,60 | US$ 0,61 |
| 40% | US$ 0,30 | US$ 0,34 |
| 25% | US$ 0,60 | US$ 0,41 |
| **25%** | **US$ 0,30** | **US$ 0,20** ← o pior caso honesto |

**Mesmo no pior caso, você não perde dinheiro.** Essa é a diferença que o DGB comprou: na configuração antiga em BTC, o pior caso era prejuízo por claim.

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
| 3 | **Abrir conta no FaucetPay** e criar a chave de API já com **teto diário** | É a sua via de pagamento — e o teto é a sua defesa nº 1 contra fraude (seção 4.2). | R$ 0 |
| 4 | **Confirmar que o DGB está disponível para você**: você consegue comprar com Pix e o par DGB/USDT tem volume? | Você vai repor estoque toda semana. Se a liquidez secar, muda para LTC (seção 3.9). | R$ 0 |
| 5 | **Escolher o nome do ponto** (a unidade interna de US$ 0,00001) | Vai aparecer em cada tela. Não pode ter "coin", "token" ou "cripto" no nome — é ponto de fidelidade, e ele **nunca é vendido**. | R$ 0 |
| 6 | **Definir o teto diário em reais** e anotar | R$ 20 é um bom começo. É o número que limita a sua perda máxima por dia. | R$ 0 |
| 7 | **Consultar um advogado** (1 conversa) | Trocar incerteza jurídica por informação. Leve a seção 8 e pergunte também sobre o sistema de pontos. | variável |
| 8 | **Consultar um contador** | CNPJ? Receita do exterior? Como declarar quem paga em cripto? | variável |
| 9 | **Anotar num papel**: meta de usuários para 12 meses | Sem número, não há como avaliar. | R$ 0 |
| 10 | **Só então** pedir a correção técnica | Código é consequência da decisão, não o contrário. | — |

**Decisões já tomadas (não voltem para a mesa sem número novo):** moeda DGB · 0,03113 DGB por claim · intervalo de 4 minutos · mínimo de 500 pontos · teto diário R$ 20 · payout ancorado em dólar e recalculado toda semana.

---

## 16. Resumo final — a verdade em 10 linhas

1. Faucet é **negócio de mídia**, não de criptomoeda.
2. Você compra atenção com pontos e revende com anúncio.
3. **Em BTC o modelo não fecha: o mínimo de saque é US$ 98 — 24 mil claims.** Em DGB é US$ 0,09.
4. **0,03113 DGB por claim a cada 4 minutos** (11 pontos). É 1/6 do Viefaucet e igual à taxa final do FreeBitco.in.
5. **1 usuário ativo = ~US$ 0,60/mês.** Para R$ 2.000/mês: ~660 pessoas voltando todo dia.
6. **Offer wall é onde está o dinheiro.** O claim é a isca.
7. **Fraude é o inimigo nº 1** — mas com o teto diário da FaucetPay, sua perda máxima é um número que você escolhe.
8. **O jogo de aposta precisa sair por causa da Lei 14.790/2023** (R$ 30 milhões de outorga).
9. **Conquistar usuários é mais difícil que programar.** O melhor faucet de DGB da pesquisa tem 4 usuários — pagar mais não compra ninguém.
10. **Custos são baixos (US$ 8/mês). O que é caro é o seu tempo, e ele é finito.**

**O caminho que faz sentido:** construir, lançar em 3 meses, operar por 12, avaliar com número na mão. Se chegar a US$ 300–600/mês líquido, você terá algo raro — um negócio real, construído por você, que quase ninguém consegue tirar do papel. Se não chegar, você terá aprendido programação, produto, marketing e operação, com US$ 100 de prejuízo. **Os dois resultados são bons negócios.**

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

### 17.5 A anatomia do modelo: por que não tinha anúncio

Você notou o sintoma: **2 sats por hora, zero anúncios, zero shortlink, zero offer wall, e um único banner levando pra Binance.** Um site com milhões de visitas não vive de ar. Vivia disto:

| # | Camada | O que era de verdade | Resultado |
|---|---|---|---|
| 1 | **Faucet** | **Isca.** 2 sats/hora ≈ **US$ 0,0016** por rolagem [4](https://best-faucets.com/freebitcoin-the-largest-faucet-has-closed-and-gone-offline) | **Negativo.** Custo de compra de tráfego |
| 2 | **Multiply BTC (Hi-Lo)** | Jogo de azar com **~5% de vantagem matemática da casa** em cada aposta [23](https://read.cash/@Treater98/full-review-of-freebitcoin-bd6f7053) | **O motor.** A maior parte da receita vinha daqui [10](https://suitableinvestmentforyou.blogspot.com/2020/03/how-do-i-earn-compound-interest-on-my-bitcoin-balance.html?m=1) |
| 3 | **Depósitos a 4,08% a.a.** | **Float.** Eles mesmos explicavam no site: o saldo dos usuários era dividido em três partes — cobrir saques, **bancar o jogo**, e expandir a **mineração própria** deles [10](https://suitableinvestmentforyou.blogspot.com/2020/03/how-do-i-earn-compound-interest-on-my-bitcoin-balance.html?m=1) | Capital de giro de graça, custando 4% ao ano |
| 4 | **Token FUN** | Eles **compraram 3,75 bilhões de tokens FUN** e passaram a vender para a própria base de 40 milhões de usuários. Premium exigia segurar de 2.500 a 500.000 FUN, travados de 1 a 12 meses [34](https://prfreebitcoin.medium.com/participate-in-freebitco-ins-2-million-fun-token-giveaway-f392cc32cab4), com **taxa de 1% na compra** [28](https://www.publish0x.com/crypto-faucets-how-much-can-you-make/freebitcoin-new-premium-membership-program-xkyeojv) | Travaram 100 milhões de FUN em horas [30](https://beincrypto.com/fun-token-dedicated-savings-program-freebitco-in/). **O FUN caiu ~90%** [1](https://thebitcoinmanual.com/articles/the-fall-of-freebitco-in/) |
| 5 | **Indicação** | 50% do claim do indicado, 0,25–0,4% das apostas, 25% dos juros — pago pelo site, não descontado do indicado [2](https://medium.com/@ethan_l/freebitco-in-earning-guide-546a66dc0389) | Aquisição de usuários |

**Resposta direta à sua pergunta:** não era *só* com depósito. O depósito era o **combustível** — dinheiro dos outros, custando 4% ao ano, usado para bancar o cassino e a mineração. O **lucro** vinha da vantagem da casa no jogo. O **FUN** foi a extração final: vender, para uma base cativa, um ativo que eles mesmos detinham aos bilhões.

O banner que você via não era anúncio. **Era marketing do próprio estoque deles.**

### 17.6 O que isso muda na sua cabeça (a parte mais importante deste plano)

**1. O faucet nunca foi o produto. Era a boca do funil.**
Você se apaixonou pela boca do funil e está construindo só ela. O FreeBitco.in dava sats de graça para transformar visita em apostador, e apostador em comprador de token. **A torneira não pagava a conta — ela comprava a pessoa.** Seu modelo (anúncio) é outro, honesto e legal, mas muito mais magro: por isso o plano empurra offer wall.

**2. Juro sobre depósito sacável a qualquer momento = banco sem reserva.**
Se o dinheiro sai quando o cara pedir e você está pagando 4% ao ano, você está operando um banco. Quando a confiança vira, todo mundo saca junto — e foi exatamente isso que travou em agosto de 2025. **Regra absoluta para o seu projeto: nunca aceite depósito, nunca prometa rendimento.**

**3. Não amarre o pagamento a um número fixo de sats sem revisão.** Olha o que aconteceu com você mesmo:

| | 2016 | 2025 |
|---|---|---|
| Pagamento por rolagem | ~2.000 sats | ~2 sats |
| Preço do BTC | ~US$ 620 | ~US$ 82.000 |
| **Valor real por rolagem** | **≈ US$ 0,012** | **≈ US$ 0,0016** |

**Você passou a ganhar ~8 vezes menos em dólar por rolagem, enquanto o Bitcoin subiu mais de 100 vezes.** Sats não são dinheiro — são uma fração de um ativo que pode dobrar. Se o BTC dobrar e você pagar 5 sats fixos, **seu custo dobra e sua receita de anúncio não**. Pague em sats, mas revise o valor a cada 90 dias (seção 12).

**4. Pontos ≠ saldo.** Lá, 1 ponto valia 1 satoshi, mas só dava para trocar a partir de **100 mil pontos** [20](https://elspethrooney.github.io/2020/10/27/a_great_review_of_freebitcoin.html). É retenção que vira dívida escondida no balanço. Se você criar pontos, **deixe claro que não são saldo sacável**, e nunca use isso para empurrar o saque para frente.

**5. Sobre os seus 300 mil satoshis (≈ 0,003 BTC, ~US$ 245):** sinto dizer, mas trate como perdido. E cuidado redobrado agora: **todo site, "suporte", perfil no Telegram ou serviço que prometer recuperar saldo do FreeBitco.in mediante taxa é golpe** — é o segundo assalto, sempre mais rápido que o primeiro [2](https://www.weex.com/learn/articles/freebitcoin-has-shut-down-what-users-should-know-fvjnx0k7bf8mgqzr64kigj9k).

### 17.7 A verdade final sobre esse caso

O FreeBitco.in não caiu porque o código era ruim. Caiu porque **fraude corroeu a margem, regulação corroeu a operação e a custódia corroeu a confiança**. As três coisas são exatamente as que você disse que não se resolvem com código.

Um gigante de 12 anos caiu por elas. A única vantagem que você tem sobre ele é poder **decidir agora**, antes de ter usuários, que tipo de negócio não quer ser.

---

## 18. Fontes consultadas (16 a 18/10/2026)

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

**Modelo de negócio do FreeBitco.in (capítulos 17.5 e 17.6)** — verificado em 17/10/2026
- https://suitableinvestmentforyou.blogspot.com/2020/03/how-do-i-earn-compound-interest-on-my-bitcoin-balance.html?m=1 — texto do próprio site: saldo dividido em 3 partes (saques, banca do jogo, mineração); renda vem do Multiply BTC
- https://read.cash/@Treater98/full-review-of-freebitcoin-bd6f7053 — vantagem da casa de ~5% no Hi-Lo
- https://prfreebitcoin.medium.com/participate-in-freebitco-ins-2-million-fun-token-giveaway-f392cc32cab4 — FreeBitco.in adquiriu 3,75 bilhões de tokens FUN
- https://freebitco.in/static/html/token/user.html — Premium: 2.500 a 500.000 FUN, trava de 1 a 12 meses
- https://www.publish0x.com/crypto-faucets-how-much-can-you-make/freebitcoin-new-premium-membership-program-xkyeojv — taxa de 1% na compra de FUN
- https://beincrypto.com/fun-token-dedicated-savings-program-freebitco-in/ — 100 milhões de FUN travados em horas
- https://www.investing.com/news/cryptocurrency-news/binancelisted-fun-token-to-tap-from-freebitcoins-41-million-users-through-premium-membership-program-launch-2409149 — 41 milhões de usuários, 52 milhões de visitas/mês
- https://medium.com/@ethan_l/freebitco-in-earning-guide-546a66dc0389 — comissões de indicação (50% claim, 0,4% aposta, 25% juros)
- https://elspethrooney.github.io/2020/10/27/a_great_review_of_freebitcoin.html — 1 ponto = 1 satoshi, mínimo de 100 mil pontos para trocar

**Moeda, microcarteira e economia (capítulos 3 e 4)** — verificado em 17–18/10/2026
- https://faucetpay.io/fees — tabela oficial: taxa e mínimo de saque por moeda (BTC 0,0012 mínimo; DGB 25 mínimo; Direct Transfer grátis)
- https://faucetpay.io/api-docs — API v2 de pagamento: sem mínimo de envio, teto diário em dólar por chave, chave de idempotência, campo de IP
- https://hackernoon.com/how-to-build-automatic-crypto-payouts-with-the-faucetpay-api — teto diário, chaves com menor privilégio, amount em menor unidade
- https://faucetpay.io/help/api/sending-payouts — fluxo de pagamento do faucet para o usuário
- https://faq.faucetpay.io/knowledge-base/what-currencies-do-you-work-with/ — moedas suportadas (inclui DGB)
- https://www.coingecko.com/en/coins/bitcoin — BTC US$ 81.468
- https://www.coingecko.com/en/coins/litecoin — LTC US$ 58,75 · US$ 4,57 bi de valor de mercado
- https://www.coingecko.com/en/coins/dash — DASH US$ 48,35 · US$ 622 mi
- https://www.coingecko.com/en/coins/digibyte — DGB ~US$ 0,00349 · US$ 64 mi · ranking #384
- https://www.coinlore.com/coin/digibyte — volume do par DGB/USDT na Binance (~US$ 58 mil/dia)

**Pesquisa de campo em faucets de DGB (capítulo 3.2)** — visitados em 18/10/2026
- https://viefaucet.com — 65 tokens (US$ 0,00065) a cada 4 min; token próprio de US$ 0,00001; mínimo de saque 500 tokens (US$ 0,005)
- https://dgb.topfaucet.top — 0,14 DGB a cada 5 min; reCAPTCHA; exige FaucetPay; lista pública de pagamentos recentes
- https://faucetpepe.xyz — 0,043 DGB a cada 10 s com teto de 1.000 claims/dia

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
