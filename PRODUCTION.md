# Configuração para Produção - Modern Faucet

## Configuração do Servidor Web

### Apache Virtual Host
```apache
<VirtualHost *:443>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    DocumentRoot /var/www/modern-faucet/frontend/dist
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/seudominio.crt
    SSLCertificateKeyFile /etc/ssl/private/seudominio.key
    SSLCertificateChainFile /etc/ssl/certs/chain.crt
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;"
    
    # API Proxy
    ProxyPass /api/ http://127.0.0.1:8000/api/
    ProxyPassReverse /api/ http://127.0.0.1:8000/api/
    ProxyPreserveHost On
    
    # Frontend SPA Configuration
    <Directory "/var/www/modern-faucet/frontend/dist">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Enable compression
        <IfModule mod_deflate.c>
            AddOutputFilterByType DEFLATE text/plain
            AddOutputFilterByType DEFLATE text/html
            AddOutputFilterByType DEFLATE text/xml
            AddOutputFilterByType DEFLATE text/css
            AddOutputFilterByType DEFLATE application/xml
            AddOutputFilterByType DEFLATE application/xhtml+xml
            AddOutputFilterByType DEFLATE application/rss+xml
            AddOutputFilterByType DEFLATE application/javascript
            AddOutputFilterByType DEFLATE application/x-javascript
        </IfModule>
        
        # Cache static assets
        <IfModule mod_expires.c>
            ExpiresActive On
            ExpiresByType text/css "access plus 1 year"
            ExpiresByType application/javascript "access plus 1 year"
            ExpiresByType image/png "access plus 1 year"
            ExpiresByType image/jpg "access plus 1 year"
            ExpiresByType image/jpeg "access plus 1 year"
            ExpiresByType image/gif "access plus 1 year"
            ExpiresByType image/svg+xml "access plus 1 year"
        </IfModule>
        
        # SPA Routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/modern-faucet-error.log
    CustomLog ${APACHE_LOG_DIR}/modern-faucet-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName seudominio.com
    ServerAlias www.seudominio.com
    Redirect permanent / https://seudominio.com/
</VirtualHost>
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name seudominio.com www.seudominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seudominio.com www.seudominio.com;
    root /var/www/modern-faucet/frontend/dist;
    index index.html;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/certs/seudominio.crt;
    ssl_certificate_key /etc/ssl/private/seudominio.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;";
    
    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # API Proxy
    location /api/ {
        proxy_pass http://127.0.0.1:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Static Assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # SPA Routing
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Logs
    access_log /var/log/nginx/modern-faucet-access.log;
    error_log /var/log/nginx/modern-faucet-error.log;
}
```

## Configuração do Backend para Produção

### .env para Produção
```env
APP_NAME=ModernFaucetSite
APP_ENV=production
APP_KEY=base64:GERE_UMA_CHAVE_SEGURA_AQUI
APP_DEBUG=false
APP_URL=https://seudominio.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=faucet_db
DB_USERNAME=faucet_user
DB_PASSWORD=SENHA_MUITO_SEGURA_AQUI

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@seudominio.com"
MAIL_FROM_NAME="${APP_NAME}"

JWT_SECRET=GERE_UMA_CHAVE_JWT_MUITO_SEGURA_AQUI
JWT_TTL=60

# Configurações da Faucet
FAUCET_CLAIM_AMOUNT=0.00000010
FAUCET_CLAIM_INTERVAL=30
FAUCET_REFERRAL_RATE=0.10
FAUCET_MIN_PAYOUT=0.00001000

# Configurações do Jogo
DICE_MIN_BET=0.00000001
DICE_MAX_BET=0.00010000
DICE_HOUSE_EDGE=0.01
```

### Supervisor Configuration (Queue Worker)
```ini
[program:modern-faucet-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/modern-faucet/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/modern-faucet/backend/storage/logs/worker.log
stopwaitsecs=3600
```

### Cron Jobs
```bash
# Adicione ao crontab do usuário www-data
* * * * * cd /var/www/modern-faucet/backend && php artisan schedule:run >> /dev/null 2>&1

# Backup diário do banco de dados
0 2 * * * mysqldump -u faucet_user -p'SENHA' faucet_db | gzip > /backups/faucet_db_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz

# Limpeza de logs antigos
0 3 * * 0 find /var/www/modern-faucet/backend/storage/logs -name "*.log" -mtime +30 -delete
```

## Configuração de Segurança

### Firewall (UFW)
```bash
# Permitir apenas portas necessárias
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### Fail2Ban
```ini
# /etc/fail2ban/jail.local
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/modern-faucet-error.log

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/modern-faucet-access.log

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/modern-faucet-access.log
```

## Monitoramento

### Logrotate
```
/var/log/apache2/modern-faucet-*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 root adm
    postrotate
        systemctl reload apache2
    endscript
}

/var/www/modern-faucet/backend/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### Script de Monitoramento
```bash
#!/bin/bash
# /usr/local/bin/monitor-faucet.sh

# Verificar se o backend está respondendo
if ! curl -f -s http://localhost:8000/api/faucet/info > /dev/null; then
    echo "Backend não está respondendo" | mail -s "Alerta: Backend Down" admin@seudominio.com
fi

# Verificar espaço em disco
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "Uso de disco: ${DISK_USAGE}%" | mail -s "Alerta: Disco Cheio" admin@seudominio.com
fi

# Verificar logs de erro
ERROR_COUNT=$(tail -100 /var/log/apache2/modern-faucet-error.log | grep -c "$(date '+%Y-%m-%d')")
if [ $ERROR_COUNT -gt 10 ]; then
    echo "Muitos erros detectados: $ERROR_COUNT" | mail -s "Alerta: Muitos Erros" admin@seudominio.com
fi
```

## Backup e Recuperação

### Script de Backup
```bash
#!/bin/bash
# /usr/local/bin/backup-faucet.sh

BACKUP_DIR="/backups/modern-faucet"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório de backup
mkdir -p $BACKUP_DIR

# Backup do banco de dados
mysqldump -u faucet_user -p'SENHA' faucet_db | gzip > $BACKUP_DIR/database_$DATE.sql.gz

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/modern-faucet --exclude=/var/www/modern-faucet/backend/storage/logs

# Remover backups antigos (manter 30 dias)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Upload para cloud (opcional)
# aws s3 cp $BACKUP_DIR/database_$DATE.sql.gz s3://seu-bucket/backups/
# aws s3 cp $BACKUP_DIR/files_$DATE.tar.gz s3://seu-bucket/backups/
```

## Otimizações de Performance

### Redis Configuration
```
# /etc/redis/redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### MySQL Optimization
```sql
-- /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 64M
max_connections = 200
```

### PHP-FPM Optimization
```ini
; /etc/php/8.1/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

## Checklist de Deploy

- [ ] Servidor configurado com SSL
- [ ] Banco de dados criado e configurado
- [ ] Variáveis de ambiente configuradas
- [ ] Dependências instaladas
- [ ] Migrações executadas
- [ ] Admin criado
- [ ] Servidor web configurado
- [ ] Firewall configurado
- [ ] Monitoramento configurado
- [ ] Backup configurado
- [ ] Logs configurados
- [ ] Performance testada
- [ ] Segurança testada

## Contatos de Emergência

Mantenha uma lista de contatos para emergências:
- Administrador do sistema
- Provedor de hospedagem
- Suporte técnico
- Responsável pelo domínio/SSL

