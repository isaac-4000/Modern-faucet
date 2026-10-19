# As três travas — o que foi construído

Data: 19/10/2026 · branch `arena/4d7bf6d3-modern-faucet` · commit `4fde1aa` (backend)

Você pediu três coisas: **captcha**, **uma conta por IP** e **saque via FaucetPay com teto diário**.
As três estão construídas, no backend e na tela.

---

## 1. Captcha (Cloudflare Turnstile)

**Onde está:** `backend/app/Services/Turnstile.php`, `backend/app/Rules/TurnstileToken.php`,
`frontend/src/components/TurnstileWidget.jsx`

**Onde é cobrado:**

| Tela | Tem captcha? | Por quê |
|---|---|---|
| Cadastro | Sim | É onde o bot cria as 500 contas |
| Claim do faucet | Sim | É onde o bot coleta |
| Login do admin | Sim | É a porta do cofre |
| Login do usuário | Não | O limite de 10 tentativas/minuto já segura força bruta. Captcha em todo login espanta gente real. |

**Por que Turnstile e não reCAPTCHA:** é gratuito e sem limite de requisições, não mostra
aquelas imagens de semáforo (na maioria das vezes o usuário nem vê nada) e funciona bem
no celular — onde está quase todo o seu tráfego.

**A decisão de projeto que mais importa:** o serviço **falha fechado**.
Se a chave secreta não estiver configurada, todo captcha é recusado.
É melhor o site parar do que o site ficar desprotegido sem ninguém notar.

---

## 2. Uma conta por IP

**Onde está:** migration `2026_10_18_000001_add_ip_columns_to_users_table.php`,
`backend/app/Http/Controllers/Api/AuthController.php` (método `register`)

O banco agora guarda três IPs: o do **cadastro**, do **último login** e do **último claim**.
O do cadastro tem índice, porque é checado em todo cadastro novo.

**Padrão: 3 contas por IP**, editável pelo painel (`max_accounts_per_ip`).

> ⚠️ **O ponto delicado, e é bom você saber:** no Brasil as operadoras usam CGNAT —
> várias pessoas reais aparecem como se fossem o mesmo IP. Coloquei 3 de padrão, não 1.
> Se começar a aparecer gente reclamando que não consegue criar conta, suba esse número
> em Configurações. Se aparecerem muitas contas do mesmo IP no painel, baixe.

O painel do admin (`/api/admin/faucet/status`) já lista os IPs com mais de uma conta.
É ali que você enxerga o ataque acontecendo.

---

## 3. Saque via FaucetPay com teto diário

**Onde está:** `backend/app/Services/FaucetPay.php`, `backend/app/Services/FaucetEconomy.php`,
`backend/app/Http/Controllers/Api/WithdrawalController.php`,
`frontend/src/pages/Withdraw.jsx`, `frontend/src/components/TankBar.jsx`

### A unidade interna virou o PONTO

Essa foi a mudança de fundo. Antes o saldo era guardado numa moeda. Agora:

```
1 ponto = US$ 0,00001   (fixo, é a âncora)
```

O DGB entra **só na hora de pagar**. Consequência prática: se o DGB dobrar de preço
amanhã, você continua pagando os mesmos 11 pontos por claim. O seu custo em dólar
não muda, e a carteira do usuário não encolhe quando a moeda cai.

### As duas travas do saque

**Trava 1 — mínimo de 500 pontos** (= US$ 0,005 = ~1,43 DGB).
Existe por um motivo prático: a FaucetPay cobra **0,25 DGB de taxa fixa por saque**.
Abaixo de 500 pontos, a taxa come mais da metade do pagamento.

**Trava 2 — a torneira.** Teto de **R$ 20 por dia**. O que passar disso não sai,
aconteça o que acontecer. É isso que rebaixa um ataque de bots de "risco existencial"
para "risco que eu já contabilizei".

Detalhes que fazem diferença:

- O teto é contado com o **valor em reais congelado no momento do saque**, não com a
  cotação do dia. Se o DGB subir à tarde, o que já foi pago não passa a valer mais e
  não estoura o teto retroativamente.
- O tanque é conferido **duas vezes**: uma antes e uma **dentro da transação**. Assim
  dois pedidos simultâneos não conseguem juntos furar o teto.
- Se a API da FaucetPay falhar, **o saldo volta para o usuário**. Dinheiro de usuário
  nunca desaparece.
- Todo saque leva um `idempotency_key`. Se a conexão cair no meio e o servidor repetir
  a chamada, a FaucetPay reconhece a chave e **não paga duas vezes**.

### A barra da torneira é pública

`GET /api/faucet/tank` não exige login, e a barra aparece na home.
Em faucet, a maior desconfiança do usuário é "será que eles pagam?".
Uma barra que seca é a prova de que o dinheiro existe e está saindo.

---

## Os números que ficaram cravados

| O quê | Valor | De onde veio |
|---|---|---|
| Intervalo | 4 minutos | sua escolha |
| Claim | 11 pontos | US$ 0,00010865 → 0,467 DGB/h |
| Por dia, se reclamar sem parar | US$ 0,0388 | 15 claims/h × 24h |
| Mínimo de saque | 500 pontos | US$ 0,005 = ~1,43 DGB |
| Teto diário | R$ 20 | o que você topa perder num dia ruim |
| Contas por IP | 3 | ajustável por causa do CGNAT |
| Aposta no dados | 1 a 50 pontos | teto de US$ 0,0005 por jogada |

Tudo isso é editável no painel do admin, **sem deploy**. Nenhum desses números está
cravado no código.

---

## O que mais veio junto

- **Comando `php artisan faucet:recalculate-claim`**, agendado para toda segunda às 6h.
  Ele mantém o custo preso no dólar, não na cotação. Sem isso, se o DGB subir o seu
  custo sobe calado e você só descobre no fim do mês.
- **`/api/admin/faucet/status`**: quanto saiu hoje, quantos sacaram, quais IPs têm mais
  de uma conta, e os últimos saques que falharam.
- **Tela de saque** (`/withdraw`): barra da torneira, progresso até o mínimo, histórico.
- **21 testes** em `backend/tests/Feature/FaucetProtectionTest.php` provando que cada
  trava funciona — inclusive que o saldo volta quando o saque falha.
- O frontend parou de mentir "Bitcoin/BTC" em tudo: agora fala em pontos e DGB.
- O endereço da API saiu do código e foi para o `.env` (`VITE_API_BASE_URL`).

---

## ⚠️ O que VOCÊ precisa fazer (só você pode)

São três contas para criar. Nenhuma cobra nada:

1. **Cloudflare Turnstile** — https://dash.cloudflare.com → Turnstile
   Pegue a **Site Key** e a **Secret Key**.
   → `TURNSTILE_SITE_KEY` e `TURNSTILE_SECRET` no `.env` do backend
   → `VITE_TURNSTILE_SITE_KEY` no `.env` do frontend

2. **FaucetPay** — https://faucetpay.io/page/faucet-admin
   Cadastre o faucet e gere a chave de API.
   → `FAUCETPAY_API_KEY` no `.env` do backend

3. **Configure o teto diário DENTRO do painel da FaucetPay também.**
   A chave de API deles aceita um limite diário em dólar. É a **segunda rede**: se a
   nossa trava falhar por algum motivo, a deles segura.

Enquanto essas chaves não estiverem no ar, o captcha recusa tudo e o saque não funciona.
Isso é de propósito — falha fechada.

---

## O que NÃO foi verificado (sendo honesto)

**Não existe PHP neste ambiente.** Então:

- Nenhuma linha do backend foi executada. Não rodei `php artisan migrate`,
  não rodei `php artisan test`, não subi o servidor.
- Validei sintaxe e balanceamento de chaves/chaves-de-bloco nos 72 arquivos PHP por
  um verificador próprio — isso pega erro de digitação grosseiro, não pega erro de lógica.
- Os 21 testes foram escritos para rodar na sua máquina. Rode assim:
  ```
  cd backend && php artisan test --filter=FaucetProtectionTest
  ```
  Se algum falhar, é porque algo aqui está errado — me mande a saída.

O que **foi** verificado de verdade: o frontend compila (`vite build`, 1680 módulos, ok)
e o preview deste sandbox está rodando com uma API falsa só para mostrar as telas.

---

## Coisas para ficar de olho quando estiver no ar

1. **`max_accounts_per_ip`** — o número que vai precisar de ajuste fino. Comece em 3 e
   olhe a lista de IPs suspeitos no painel toda semana.
2. **O teto de R$ 20** — se a torneira secar todo dia antes do meio-dia, é sinal de que
   o tráfego cresceu. Suba o teto junto com a receita, nunca antes.
3. **O preço do DGB** — o comando recalcula sozinho toda segunda, mas se a moeda
   disparar no meio da semana, atualize `dgb_usd_price` à mão no painel.
4. **Saques que falharam** — aparecem no `/api/admin/faucet/status`. Se começarem a
   aparecer muitos, o mais provável é saldo insuficiente na FaucetPay.
