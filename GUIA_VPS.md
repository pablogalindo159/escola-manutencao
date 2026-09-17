# 🚀 GUIA DE INSTALAÇÃO NA VPS - ESCOLA DA MANUTENÇÃO

> 4 passos simples para ter o sistema rodando em produção

---

## 📋 Pré-Requisitos

- ✅ VPS Linux (Ubuntu 20.04+ ou similar)
- ✅ Acesso SSH como root
- ✅ Internet estável
- ✅ Domínio configurado (opcional, pode usar IP)

---

## ⚡ INSTALAÇÃO RÁPIDA (Opção 1 - Recomendado)

### Passo 1: Conectar na VPS
```bash
ssh root@seu_ip_vps
```

### Passo 2: Executar Script Automático
```bash
bash <(curl https://raw.githubusercontent.com/pablogalindo159/escola-manutencao/main/scripts/install-vps-linux.sh)
```

**Aguarde ~5 minutos...**

### Passo 3: Acessar Sistema
```
URL: http://seu_ip_vps:8000
Email: admin@example.com
Senha: password
```

### Passo 4: Confirmar Funcionamento
```bash
# Ver logs
tail -f /var/log/laravel.log

# Verificar containers Docker
docker ps

# Testar API
curl http://localhost:8000/api/auth/login
```

---

## 🛠️ INSTALAÇÃO MANUAL (Opção 2)

Se preferir instalar passo a passo:

### Passo 1: Atualizar Sistema
```bash
apt update && apt upgrade -y
apt install -y curl git apt-transport-https ca-certificates software-properties-common
```

### Passo 2: Instalar Docker
```bash
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -
add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
apt update && apt install -y docker-ce docker-ce-cli containerd.io docker-compose
systemctl enable docker && systemctl start docker
```

### Passo 3: Clonar Repositório
```bash
cd /tmp
git clone https://github.com/pablogalindo159/escola-manutencao.git escola-novo
cd escola-novo
```

### Passo 4: Iniciar Docker
```bash
docker-compose up -d
```

**Aguarde até que PostgreSQL, Redis e Laravel estejam prontos (~30 segundos)**

### Passo 5: Instalar Dependências Laravel
```bash
docker-compose exec app composer install --no-interaction
```

### Passo 6: Configurar Ambiente
```bash
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
```

### Passo 7: Banco de Dados
```bash
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed
```

### Passo 8: Criar Usuário Admin
```bash
docker-compose exec app php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('Senha123!')])
>>> exit
```

### Passo 9: Compilar Assets
```bash
docker-compose exec app npm install
docker-compose exec app npm run build
```

### Passo 10: Testar
```bash
# Verificar containers
docker ps

# Acessar
curl http://localhost:8000
```

---

## ✅ VERIFICAÇÃO DE INSTALAÇÃO

### Checklist Final

```bash
# 1. Docker containers rodando
docker ps
# Deve listar: app, postgres, redis, nginx

# 2. Laravel pronto
curl -s http://localhost:8000 | grep -i "<!DOCTYPE"

# 3. API funcionando
curl -s http://localhost:8000/api/auth/login -X POST

# 4. Banco de dados
docker-compose exec app php artisan tinker
>>> User::count()
>>> exit

# 5. Logs sem erros
docker-compose logs app | tail -20
```

---

## 🌐 CONFIGURAR DOMÍNIO (Opcional)

### 1. Apontar Domínio
Configurar A record do seu domínio para o IP da VPS:
```
A    escoladamanutencao.com.br    seu_ip_vps
```

### 2. Atualizar .env
```bash
docker-compose exec app nano .env
```

Alterar:
```
APP_URL=http://escoladamanutencao.com.br
```

### 3. Gerar SSL (Let's Encrypt)
```bash
docker-compose exec app certbot certonly -d escoladamanutencao.com.br
```

### 4. Restart
```bash
docker-compose restart app
```

---

## 📊 MONITORAMENTO

### Ver Logs em Tempo Real
```bash
docker-compose logs -f app
```

### Ver Status dos Containers
```bash
docker ps
docker stats
```

### Backup Automático
```bash
# Banco PostgreSQL
docker-compose exec postgres pg_dump -U postgres escola_manutencao > backup_$(date +%Y%m%d).sql

# Copiar para fora do servidor
scp root@seu_ip:/tmp/backup_*.sql .
```

---

## 🔧 TROUBLESHOOTING

### Problema: "Docker not found"
```bash
apt install -y docker.io docker-compose
```

### Problema: "Permission denied"
```bash
# Se não for root
sudo su -
# ou adicione ao docker group
sudo usermod -aG docker $USER
```

### Problema: "Port 8000 already in use"
```bash
# Ver qual processo usa a porta
lsof -i :8000

# Usar outra porta no docker-compose.yml
# Alterar: ports: - "9000:8000"
```

### Problema: "Database connection failed"
```bash
# Aguarde mais tempo (PostgreSQL leva tempo para iniciar)
sleep 10
docker-compose restart app

# Ou check manual
docker-compose exec postgres psql -U postgres -d escola_manutencao -c "SELECT 1"
```

### Problema: "Migration failed"
```bash
# Deletar dados e refazer
docker-compose exec app php artisan migrate:fresh --seed

# Ou com rollback
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan migrate
```

---

## 🚀 PRÓXIMOS PASSOS

### 1. Customizar Marca
```bash
docker-compose exec app nano resources/views/layouts/app.blade.php
# Alterar logo, cores, etc
```

### 2. Configurar Pagamentos (Mercado Pago)
```bash
docker-compose exec app nano .env
# Adicionar:
# MERCADO_PAGO_PUBLIC_KEY=seu_token
# MERCADO_PAGO_ACCESS_TOKEN=seu_token
```

### 3. Configurar Notificações (Firebase)
```bash
docker-compose exec app nano .env
# Adicionar:
# FIREBASE_PROJECT_ID=seu_project
# FIREBASE_PRIVATE_KEY=sua_chave
```

### 4. Fazer Deploy em Produção
```bash
# Usar script de deploy automático
bash scripts/deploy-production.sh seu_dominio.com.br

# Ou manual
docker-compose -f docker-compose.prod.yml up -d
```

---

## 📱 TESTAR APP FLUTTER

### Conectar App ao Backend da VPS
```
// Em flutter_app/lib/services/api_service.dart
const String API_BASE_URL = 'http://seu_ip_vps:8000/api';
```

### Build APK para Android
```bash
cd flutter_app
flutter build apk --release
# APK em: build/app/outputs/flutter-app-release.apk
```

### Build IPA para iOS
```bash
cd flutter_app
flutter build ios --release
```

---

## ✨ PARABÉNS! 🎉

Seu sistema Escola da Manutenção está rodando em produção!

### URLs Importantes
- 🌐 Website: http://seu_ip_vps:8000
- 📱 API: http://seu_ip_vps:8000/api
- 📊 Admin: http://seu_ip_vps:8000/admin
- 📖 API Docs: http://seu_ip_vps:8000/api/docs

### Credenciais Padrão
- Email: admin@example.com
- Senha: password

**⚠️ MUDE ESSAS CREDENCIAIS IMEDIATAMENTE!**

---

## 📞 Precisa de Ajuda?

```
WhatsApp: (41) 3283-0558
Email: suporte@escoladamanutencao.com.br
GitHub: https://github.com/pablogalindo159/escola-manutencao/issues
```
