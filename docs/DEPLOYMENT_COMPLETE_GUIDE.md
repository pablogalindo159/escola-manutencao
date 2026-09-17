# 🚀 Escola da Manutenção - Deployment Completo

## 📋 Índice

1. [Setup VPS (DigitalOcean)](#setup-vps-digitalocean)
2. [Deploy Backend (Laravel)](#deploy-backend-laravel)
3. [Deploy App Flutter](#deploy-app-flutter)
4. [Deploy Website (Laravel Blade)](#deploy-website-laravel-blade)
5. [CI/CD Pipeline](#cicd-pipeline)
6. [Monitoring & Backup](#monitoring--backup)

---

## 1️⃣ Setup VPS (DigitalOcean)

### 1.1 Criar Droplet

```bash
# Especificações recomendadas:
- OS: Ubuntu 22.04 LTS
- Tamanho: $24/mês (4GB RAM, 80GB SSD, 4 vCPUs)
- Região: São Paulo (sfo3)
- SSH Key: gerar localmente

# Gerar SSH key (local)
ssh-keygen -t rsa -b 4096 -f ~/.ssh/escola_key

# Adicionar ao DigitalOcean Dashboard
```

### 1.2 Configuração Inicial

```bash
# Conectar ao Droplet
ssh -i ~/.ssh/escola_key root@SEU_IP

# Atualizar sistema
apt update && apt upgrade -y

# Instalar dependências
apt install -y curl wget git unzip sqlite3 \
  build-essential htop net-tools tmux vim nano

# Configurar timezone
timedatectl set-timezone America/Sao_Paulo

# Desabilitar root, criar usuário
useradd -m -s /bin/bash appuser
usermod -aG sudo appuser
sudo su - appuser
```

### 1.3 Instalar Stack de Desenvolvimento

```bash
# PHP 8.2 + Extensions
curl https://packages.sury.org/php/apt.gpg | sudo apt-key add -
sudo add-apt-repository "deb https://packages.sury.org/php/ $(lsb_release -sc) main"
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-pdo php8.2-mysql php8.2-pgsql \
  php8.2-redis php8.2-curl php8.2-xml php8.2-json php8.2-zip php8.2-gd

# PostgreSQL 15
sudo apt install -y postgresql postgresql-contrib postgresql-15

# Redis
sudo apt install -y redis-server

# Node.js + npm
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Docker + Docker Compose (opcional, para containers)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Nginx
sudo apt install -y nginx

# SSL (Certbot)
sudo apt install -y certbot python3-certbot-nginx
```

### 1.4 Criar Banco de Dados

```bash
# Conectar ao PostgreSQL
sudo -u postgres psql

# Criar database e usuário
CREATE DATABASE escola_manutencao;
CREATE USER escola_app WITH PASSWORD 'senha_super_segura_aqui';
ALTER ROLE escola_app SET client_encoding TO 'utf8';
ALTER ROLE escola_app SET default_transaction_isolation TO 'read committed';
ALTER ROLE escola_app SET default_transaction_deferrable TO on;
GRANT ALL PRIVILEGES ON DATABASE escola_manutencao TO escola_app;
\q
```

---

## 2️⃣ Deploy Backend (Laravel)

### 2.1 Clonar e Setup

```bash
cd /var/www
sudo git clone https://github.com/seurepo/escola-manutencao-backend.git
cd escola-manutencao-backend

# Copiar .env
sudo cp .env.example .env

# Editar .env com credenciais reais
sudo nano .env
```

**Arquivo .env (produção)**:

```env
APP_NAME="Escola da Manutenção"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GERAR_COM_php artisan key:generate
APP_URL=https://api.escoladamanutencao.com.br

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=escola_manutencao
DB_USERNAME=escola_app
DB_PASSWORD=senha_super_segura_aqui

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_username
MAIL_PASSWORD=seu_password
MAIL_FROM_ADDRESS=noreply@escoladamanutencao.com.br

MERCADO_PAGO_TOKEN=SUA_CHAVE_PROD
FIREBASE_PROJECT_ID=seu_project_id
FIREBASE_API_KEY=sua_chave_firebase

JWT_SECRET=seu_jwt_secret_aqui
```

### 2.2 Instalar Dependências e Rodar

```bash
# Instalar composer packages
composer install --optimize-autoloader --no-dev

# Gerar APP_KEY
php artisan key:generate

# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissões
sudo chown -R www-data:www-data /var/www/escola-manutencao-backend
chmod -R 755 storage bootstrap/cache
```

### 2.3 Configurar Nginx

```bash
# Criar config
sudo nano /etc/nginx/sites-available/api.escoladamanutencao.com.br
```

**Conteúdo**:

```nginx
upstream php-backend {
    server 127.0.0.1:9000;
}

server {
    listen 80;
    server_name api.escoladamanutencao.com.br;
    root /var/www/escola-manutencao-backend/public;

    index index.php index.html index.htm;

    # Redirecionar HTTP → HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.escoladamanutencao.com.br;
    root /var/www/escola-manutencao-backend/public;

    # SSL (gerado com Certbot)
    ssl_certificate /etc/letsencrypt/live/api.escoladamanutencao.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.escoladamanutencao.com.br/privkey.pem;

    # Headers de segurança
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript 
               application/x-javascript application/xml+rss 
               application/javascript application/json;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php-backend;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 2.4 Ativar Site

```bash
sudo ln -s /etc/nginx/sites-available/api.escoladamanutencao.com.br \
           /etc/nginx/sites-enabled/

# Testar config
sudo nginx -t

# Reload
sudo systemctl reload nginx

# SSL com Let's Encrypt
sudo certbot certonly --nginx -d api.escoladamanutencao.com.br
```

### 2.5 PHP-FPM Service

```bash
# Editar pool PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Configurações importantes:
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 2
pm.max_spare_servers = 5

sudo systemctl restart php8.2-fpm
sudo systemctl enable php8.2-fpm
```

---

## 3️⃣ Deploy App Flutter

### 3.1 Android - Play Store

```bash
# Gerar keystore
keytool -genkey -v -keystore ~/android_keystore/escola_keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias escola_prod

# Criar arquivo key.properties
mkdir -p android
cat > android/key.properties << EOF
storePassword=SENHA_KEYSTORE
keyPassword=SENHA_KEY
keyAlias=escola_prod
storeFile=../android_keystore/escola_keystore.jks
EOF

# Atualizar build.gradle (Android)
# Já feito no projeto, verificar

# Build release APK/Bundle
flutter build appbundle --release

# Arquivo gerado: build/app/outputs/bundle/release/app-release.aab
```

### 3.2 iOS - App Store

```bash
# Obter certificados (Apple Developer Account)
# 1. Gerar Certificate Signing Request (CSR) no Keychain
# 2. Fazer upload no Apple Developer
# 3. Download do .cer

# Criar Provisioning Profile
# Baixar no Apple Developer console

# Build iOS
flutter build ios --release

# Abrir no Xcode para submit
open ios/Runner.xcworkspace

# Ou fazer push direto:
xcrun altool --upload-app --type ios \
  --file "./build/ios/ipa/Runner.ipa" \
  --username seu_email@apple.com \
  --password SEU_APP_PASSWORD
```

### 3.3 Firebase Setup no App

```bash
# Já feito com flutterfire configure
# Verificar arquivos:
# - google-services.json (Android)
# - GoogleService-Info.plist (iOS)
```

### 3.4 Mercado Pago Keys

```dart
// Em lib/config/mercado_pago_config.dart
// Atualizar com chaves de produção

const String MERCADO_PAGO_PUBLIC_KEY = 'APP_USR-xxxxxxxxxxxx';
```

---

## 4️⃣ Deploy Website (Laravel Blade)

### 4.1 Nginx Config

```bash
sudo nano /etc/nginx/sites-available/escoladamanutencao.com.br
```

**Config idêntica ao API, mas apontando para /public/**

```nginx
root /var/www/escola-manutencao-website/public;
```

### 4.2 Ativar

```bash
sudo ln -s /etc/nginx/sites-available/escoladamanutencao.com.br \
           /etc/nginx/sites-enabled/

sudo certbot certonly --nginx -d escoladamanutencao.com.br

sudo systemctl reload nginx
```

---

## 5️⃣ CI/CD Pipeline

### 5.1 GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/escola-manutencao-backend
            git pull origin main
            composer install --no-dev
            php artisan migrate --force
            php artisan cache:clear
            php artisan config:cache
            sudo systemctl reload php8.2-fpm
            sudo systemctl reload nginx
            
            # Notificar sucesso (webhook ou email)
```

### 5.2 Setup Secrets no GitHub

```
- HOST: seu_ip
- USERNAME: appuser
- SSH_KEY: conteúdo da chave privada
- SLACK_WEBHOOK: para notificações
```

---

## 6️⃣ Monitoring & Backup

### 6.1 Setup Monit (Monitoring)

```bash
sudo apt install -y monit

sudo nano /etc/monit/monitrc
```

**Configurações**:

```
set daemon 60
set logfile /var/log/monit.log
set idfile /var/lib/monit/id

# Monitorar PHP-FPM
check process php-fpm with pidfile /run/php/php8.2-fpm.pid
    start program = "/bin/systemctl start php8.2-fpm"
    stop program = "/bin/systemctl stop php8.2-fpm"
    if does not exist then restart

# Monitorar Nginx
check process nginx with pidfile /run/nginx.pid
    start program = "/bin/systemctl start nginx"
    stop program = "/bin/systemctl stop nginx"
    if does not exist then restart

# Monitorar Redis
check process redis with pidfile /run/redis/redis-server.pid
    start program = "/bin/systemctl start redis-server"
    stop program = "/bin/systemctl stop redis-server"
    if does not exist then restart

# Monitorar PostgreSQL
check process postgresql with pidfile /var/run/postgresql/main.pid
    start program = "/bin/systemctl start postgresql"
    stop program = "/bin/systemctl stop postgresql"
```

```bash
sudo systemctl restart monit
```

### 6.2 Backup Automático (Cron)

```bash
crontab -e

# Backup diário de BD + uploads (3h da manhã)
0 3 * * * /usr/local/bin/backup-escola.sh

# Rotar logs (diariamente)
0 0 * * * /usr/sbin/logrotate /etc/logrotate.conf
```

**Script `/usr/local/bin/backup-escola.sh`**:

```bash
#!/bin/bash

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/home/backups/escola
SPACE=$BACKUP_DIR/backup_$DATE

mkdir -p $SPACE

# Backup BD
PGPASSWORD=senha_super_segura_aqui pg_dump \
  -h 127.0.0.1 \
  -U escola_app \
  -d escola_manutencao \
  -F custom \
  -f $SPACE/database.dump

# Backup storage
tar -czf $SPACE/storage.tar.gz /var/www/escola-manutencao-backend/storage

# Upload para S3 (optional)
aws s3 sync $SPACE s3://seu-bucket/backups/escola/

# Manter últimos 30 dias
find $BACKUP_DIR -type d -name "backup_*" -mtime +30 -exec rm -rf {} \;

# Log
echo "Backup concluído em $DATE" >> /var/log/backup.log
```

```bash
chmod +x /usr/local/bin/backup-escola.sh
```

### 6.3 CloudFlare CDN (opcional)

```
1. Adicionar domínio no CloudFlare
2. Atualizar nameservers no registrador
3. Ativar:
   - Full SSL
   - Auto Minify (CSS, JS, HTML)
   - Caching Level: Cache Everything
   - Browser Cache TTL: 1 month
```

---

## 📊 Checklist Final

- [ ] VPS configurado e acessível
- [ ] PostgreSQL rodando com BD criado
- [ ] Redis ativo
- [ ] Laravel backend deployado
- [ ] Website deployado
- [ ] SSL certificates válidos
- [ ] CI/CD pipeline funcionando
- [ ] Backup automático ativo
- [ ] Monitoring configurado
- [ ] App Flutter na Play Store
- [ ] App Flutter na App Store

---

## 🆘 Troubleshooting

**502 Bad Gateway?**
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
tail -f /var/log/nginx/error.log
```

**Permissões storage?**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

**Redis não conecta?**
```bash
sudo systemctl restart redis-server
redis-cli ping
```

**BD cheio?**
```bash
PGPASSWORD=senha pg_dump -d escola_manutencao | gzip > backup.sql.gz
```

---

**Versão**: 1.0.0  
**Última atualização**: 2026-09-16  
**Suporte**: support@escoladamanutencao.com.br
