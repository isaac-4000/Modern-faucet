# Guia de Instalação Rápida - Modern Faucet

## Pré-requisitos
- PHP 8.1+
- MySQL 8.0+
- Node.js 16+
- Composer
- NPM

## Instalação

### 1. Clone o projeto
```bash
git clone [repository-url]
cd modern_faucet_site
```

### 2. Configure o Backend
```bash
cd backend
composer install
cp .env.example .env
# Edite o .env com suas configurações de banco
php artisan key:generate
php artisan migrate
```

### 3. Configure o Frontend
```bash
cd ../frontend
npm install
# Crie o arquivo .env com VITE_API_URL
npm run build
```

### 4. Crie um Admin
```bash
cd ../backend
php artisan tinker
# No console: App\Models\Admin::create(['username' => 'admin', 'password' => bcrypt('admin123')]);
```

### 5. Inicie os Serviços
```bash
# Backend (desenvolvimento)
cd backend
php artisan serve --host=0.0.0.0 --port=8000

# Frontend (desenvolvimento)
cd frontend
npm run dev
```

## Acesso
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api
- Admin: http://localhost:5173/admin/login (admin/admin123)

## Configurações Importantes

### Arquivo .env do Backend
```
APP_NAME=ModernFaucetSite
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=faucet_db
DB_USERNAME=faucet_user
DB_PASSWORD=faucet_password
```

### Arquivo .env do Frontend
```
VITE_API_URL=http://localhost:8000/api
VITE_APP_NAME=Modern Faucet
```

## Funcionalidades Testadas
✅ Registro e login de usuários
✅ Sistema de faucet com claims
✅ Jogo de dados provably fair
✅ Painel administrativo completo
✅ Sistema de referências
✅ Responsividade mobile
✅ Segurança e validações

## Credenciais de Teste
- **Admin**: admin / admin123
- **Usuário**: Registre-se na aplicação

## Estrutura do Projeto
```
modern_faucet_site/
├── backend/          # Laravel API
├── frontend/         # React SPA
├── README.md         # Documentação completa
└── INSTALL.md        # Este guia
```

## Próximos Passos
1. Configure SSL para produção
2. Configure servidor web (Apache/Nginx)
3. Implemente backup automático
4. Configure monitoramento
5. Personalize design conforme necessário

## Suporte
Consulte o README.md para documentação completa e resolução de problemas.

