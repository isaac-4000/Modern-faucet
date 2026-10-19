# Como verificar a correção de segurança do admin

**Data:** 18/10/2026
**O que foi corrigido:** as rotas `/api/admin/*` usavam apenas `auth:sanctum`, que aceita **qualquer** token válido — inclusive o de um usuário comum. Na prática, qualquer pessoa que criasse uma conta no faucet conseguia alterar o próprio saldo e criar administradores.

---

## 1. O que mudou, em uma linha

Antes: `auth:sanctum` → "este token é válido?" ✅ passa para qualquer usuário.

Agora: `auth:sanctum` **+** `auth.admin` → "o dono do token é da tabela `admins` **e** o token tem a habilidade `admin`?"

## 2. Arquivos

| Arquivo | O que faz |
|---|---|
| `app/Http/Middleware/EnsureAuthenticatedAsAdmin.php` | **Novo.** Bloqueia as rotas de admin em duas camadas |
| `app/Http/Middleware/EnsureAuthenticatedAsUser.php` | **Novo.** Impede que token de admin jogue no faucet/dado |
| `app/Http/Kernel.php` | Registra os aliases `auth.admin` e `auth.user` |
| `routes/api.php` | Aplica os middlewares; `/logout` fica fora (serve para os dois) |
| `app/Providers/RouteServiceProvider.php` | Limites de tentativa de login (10/min usuário, 5/min admin, 10/min cadastro) |
| `app/Http/Controllers/Api/AuthController.php` | Token de admin passa a carregar a habilidade `admin` |
| `app/Http/Controllers/Api/AdminController.php` | Senha de admin mínimo 12 caracteres + log de auditoria |
| `database/seeders/AdminSeeder.php` | **Novo.** Cria o primeiro administrador |
| `database/factories/UserFactory.php` | Reescrita (usava colunas que não existem na tabela) |
| `tests/Feature/AdminAuthorizationTest.php` | **Novo.** 12 testes que provam a correção |
| `.env.example` | `ADMIN_USERNAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` |

---

## 3. Como criar o primeiro administrador

Esse era outro problema: a rota que cria administradores exigia que você já fosse administrador. **Nunca existiria o primeiro.**

Defina no `.env`:

```env
ADMIN_USERNAME=seu_usuario
ADMIN_EMAIL=seu@email.com
ADMIN_PASSWORD=uma_senha_bem_forte
```

Rode:

```bash
php artisan migrate --seed
```

Se `ADMIN_PASSWORD` ficar vazio, o seeder gera uma senha e mostra no terminal. **Anote — ela não aparece de novo.**

---

## 4. Rodando os testes

```bash
cd backend
composer install
php artisan test --filter=AdminAuthorizationTest
```

Esperado: **11 testes passando.** Se algum falhar, a correção não está valendo — não publique o site.

Os testes rodam em SQLite em memória (configurado no `phpunit.xml`), então não encostam no seu banco real.

---

## 5. Verificação manual com curl

Se preferir conferir na mão, com o servidor rodando (`php artisan serve`):

### Passo 1 — crie um usuário comum e pegue o token

```bash
curl -s -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"wallet_address":"carteira-teste-001","password":"123456"}'
```

Guarde o `"token"` da resposta.

### Passo 2 — crie o admin e pegue o token dele

```bash
curl -s -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"username":"seu_usuario","password":"sua_senha"}'
```

Guarde o `"token"` da resposta.

### Passo 3 — o teste que importa

Substitua `TOKEN_USUARIO` e `TOKEN_ADMIN` pelos valores acima.

```bash
# Usuário comum tentando abrir o painel  →  deve dar 403
curl -s -o /dev/null -w "%{http_code}\n" \
  -H "Authorization: Bearer TOKEN_USUARIO" \
  http://localhost:8000/api/admin/dashboard

# Usuário comum tentando se dar saldo  →  deve dar 403
curl -s -o /dev/null -w "%{http_code}\n" -X PUT \
  -H "Authorization: Bearer TOKEN_USUARIO" \
  -H "Content-Type: application/json" \
  -d '{"balance":999999}' \
  http://localhost:8000/api/admin/users/1/balance

# Usuário comum tentando criar um admin  →  deve dar 403
curl -s -o /dev/null -w "%{http_code}\n" -X POST \
  -H "Authorization: Bearer TOKEN_USUARIO" \
  -H "Content-Type: application/json" \
  -d '{"username":"invasor","password":"senha-super-forte-123"}' \
  http://localhost:8000/api/admin/admins

# Admin abrindo o painel  →  deve dar 200
curl -s -o /dev/null -w "%{http_code}\n" \
  -H "Authorization: Bearer TOKEN_ADMIN" \
  http://localhost:8000/api/admin/dashboard
```

**Resultado esperado, nesta ordem:**

```
403
403
403
200
```

Se aparecer `200` em qualquer uma das três primeiras, **a correção não está aplicada** — confira se o `Kernel.php` tem os aliases e se o `routes/api.php` tem `auth.admin` no grupo.

### Passo 4 — separação entre as contas

```bash
# Token de ADMIN tentando reclamar no faucet  →  deve dar 403
curl -s -o /dev/null -w "%{http_code}\n" -X POST \
  -H "Authorization: Bearer TOKEN_ADMIN" \
  http://localhost:8000/api/faucet/claim
```

Esperado: `403`. Um administrador não deve conseguir jogar com a conta de operação.

---

## 6. Antes de publicar: troque os tokens

Se o site já rodou em qualquer ambiente com usuários, **os tokens antigos não têm a habilidade `admin`**. Peça para todo mundo fazer login de novo — ou, mais simples, limpe a tabela:

```sql
TRUNCATE TABLE personal_access_tokens;
```

Depois é só fazer login novamente.

---

## 7. O que esta correção NÃO resolve

Fica registrado para não virar falsa sensação de segurança:

- **Ainda não existe captcha** (Turnstile) no cadastro nem no claim.
- **Ainda não existe limite de uma conta por IP/dispositivo.**
- **O intervalo de claim ainda é genérico** (o plano prevê 4 minutos).
- **Ainda não existe integração de saque** (FaucetPay) — o saldo hoje é só um número no banco.
- **O rate limit de cadastro é por IP**, e no Brasil as operadoras usam CGNAT: vários clientes reais aparecem como o mesmo IP. Se usuários legítimos forem bloqueados, suba o número em `RouteServiceProvider`.

Tudo isso está no plano de negócio, mês 1.
