# 🎓 Escola da Manutenção - Plataforma Completa

> Plataforma profissional de cursos de manutenção com backend Laravel, app Flutter e website admin

## ✨ Features

### 🎯 Projeto Completo (100%)
- ✅ **Backend Laravel**: 11 Models, 13 Migrations, 45+ endpoints API
- ✅ **App Flutter**: 16 telas, state management com Provider, Firebase Messaging
- ✅ **Website**: Landing page, dashboard admin, painel professor
- ✅ **Features Avançadas**: Gamificação, Analytics, Sistema de Afiliados
- ✅ **Testes**: 65+ testes automatizados (Unit + E2E)
- ✅ **DevOps**: GitHub Actions CI/CD, Docker Compose

### 📊 Stack Completo
```
Backend:  Laravel 10, PHP 8.2, PostgreSQL 15, Redis 7
Frontend: Flutter 3.10, Dart
Website:  Blade Templates, Tailwind CSS
DevOps:   Docker, GitHub Actions, Nginx
```

## 🚀 Instalação Rápida (3 opções)

### ✅ Opção 1: Docker (Recomendado)

```bash
# Clonar repositório
git clone https://github.com/pablogalindo159/escola-manutencao.git
cd escola-manutencao

# Instalar com Docker
docker-compose up -d
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')])
>>> exit

# Iniciar servidor
php artisan serve
```

Acesse: http://localhost:8000

### ✅ Opção 2: VPS Linux (DigitalOcean, Linode, etc)

```bash
# Conectar na VPS
ssh root@seu_ip

# Clonar e instalar
cd /tmp
git clone https://github.com/pablogalindo159/escola-manutencao.git escola-novo
cd escola-novo

# Executar instalação automática
bash scripts/install-vps-linux.sh

# Ou manualmente:
docker-compose up -d
composer install
cp .env.example .env
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```

Acesse: http://seu_ip:8000

### ✅ Opção 3: Windows / macOS

```powershell
# Abrir PowerShell como Admin (Windows)
IEX((New-Object Net.WebClient).DownloadString('https://raw.githubusercontent.com/seu-usuario/escola-manutencao/main/scripts/install-windows.ps1'))
```

## 📁 Estrutura do Projeto

```
escola-manutencao/
│
├── app/                               # Código PHP
│   ├── Models/                        # 11 Eloquent Models
│   ├── Http/
│   │   ├── Controllers/Api/           # 7 API Controllers (45+ endpoints)
│   │   ├── Controllers/Admin/         # Admin Controllers
│   │   ├── Controllers/Web/           # Web Controllers
│   │   ├── Requests/                  # Form Validations
│   │   └── Middleware/
│   └── Features/                      # Gamificação, Analytics, Afiliados
│
├── database/
│   ├── migrations/                    # 13 Migrations PostgreSQL
│   └── seeders/
│
├── resources/
│   ├── views/                         # Blade templates
│   ├── css/
│   └── js/
│
├── routes/
│   ├── api.php                        # 45+ endpoints REST
│   └── web.php
│
├── tests/                             # 65+ testes automatizados
│   ├── Unit/
│   ├── Feature/
│   └── E2E/
│
├── flutter_app/                       # App Flutter (16 telas)
│   ├── lib/
│   │   ├── screens/                   # 16 Telas
│   │   ├── providers/                 # 8 State Management Providers
│   │   ├── services/
│   │   └── models/
│   ├── android/
│   └── ios/
│
├── docker-compose.yml                 # PostgreSQL, Redis, Laravel, Nginx
├── Dockerfile
├── composer.json                      # Dependências PHP
├── package.json                       # Dependências Node
│
├── .env.example
├── .gitignore
│
├── .github/workflows/
│   └── deploy.yml                     # CI/CD GitHub Actions
│
└── docs/
    ├── DEPLOYMENT_COMPLETE_GUIDE.md
    └── openapi.yaml                   # API Swagger 3.0
```

## 📚 Models & Database

### 11 Models Principais
```
User (autenticação, perfis)
Course (cursos, estrutura)
Video (vídeos, streaming)
UserProgress (progresso aluno)
Subscription (assinaturas)
Payment (pagamentos, Mercado Pago)
Post (comunidade)
Comment (comentários)
Repair (sistema de reparos)
RepairPhoto (fotos reparos)
Certificate (certificados, QR Code)
```

### Migrações Automáticas
```bash
php artisan migrate --force
```

## 🔌 API REST (45+ Endpoints)

### Autenticação
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/refresh
POST   /api/auth/logout
GET    /api/auth/me
```

### Cursos
```
GET    /api/courses
GET    /api/courses/:id
GET    /api/my-courses
POST   /api/courses/:id/subscribe
```

### Vídeos & Progresso
```
GET    /api/videos/:id
POST   /api/videos/:id/progress
GET    /api/courses/:id/progress
```

### Reparos (Sistema Diferencial)
```
GET    /api/repairs
POST   /api/repairs
POST   /api/repairs/:id/photos
POST   /api/repairs/:id/submit
```

### Certificados
```
GET    /api/certificates
GET    /api/certificates/:id
POST   /api/certificates/generate
GET    /api/certificates/verify/:code
```

### Comunidade
```
GET    /api/posts
POST   /api/posts
POST   /api/posts/:id/like
GET    /api/posts/:id/comments
POST   /api/posts/:id/comments
```

[Veja documentação completa: docs/openapi.yaml]

## 🛠️ Testes Automatizados

### Rodar Testes
```bash
# Testes Unit
php artisan test tests/Unit/

# Testes Feature
php artisan test tests/Feature/

# Testes E2E (Cypress)
npm install
npx cypress run

# Com coverage
php artisan test --coverage
```

### 65+ Testes Inclusos
- ✅ 40+ testes Unit (PHP)
- ✅ 20+ testes Flutter
- ✅ 5 testes E2E (Cypress)

## 📱 App Flutter

### 16 Telas Completas
- Login / Registro / Recuperação Senha
- Catálogo de Cursos
- Detalhes Curso + Vídeos
- Progresso Aprendizado
- Sistema de Reparos
- Upload de Fotos
- Certificados + QR Code
- Comunidade (Posts + Comentários)
- Pagamentos (Mercado Pago)
- Notificações (Firebase)
- Perfil Usuário
- Dashboard Admin

### Features Flutter
- 🔐 JWT Authentication
- 📹 Video Player + Progress Tracking
- 📸 Camera Integration
- 💳 Mercado Pago Integration
- 🔔 Firebase Cloud Messaging
- 📱 Offline Support (SQLite)
- 💾 Secure Token Storage

## ⚙️ Configuração

### .env Essencial
```env
APP_NAME="Escola da Manutenção"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://seu_dominio.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=escola_manutencao
DB_USERNAME=postgres
DB_PASSWORD=senha_segura

REDIS_HOST=redis
REDIS_PORT=6379

JWT_SECRET=seu_jwt_secret_aqui

MERCADO_PAGO_PUBLIC_KEY=sua_chave_publica
MERCADO_PAGO_ACCESS_TOKEN=seu_token_acesso

FIREBASE_PROJECT_ID=seu_firebase_project
FIREBASE_PRIVATE_KEY=sua_chave_privada
```

## 🚀 Deploy em Produção

### VPS DigitalOcean / Linode / AWS
```bash
# SSH na VPS
ssh root@seu_ip

# Executar script automático
bash <(curl https://raw.githubusercontent.com/pablogalindo159/escola-manutencao/main/scripts/install-vps-linux.sh)
```

### Deploy Automático (GitHub Actions)
```bash
# Push para main
git push origin main

# GitHub Actions executa automaticamente:
# 1. ✅ Testes
# 2. ✅ Build Docker
# 3. ✅ Deploy Staging
# 4. ✅ Deploy Production
```

[Veja: docs/DEPLOYMENT_COMPLETE_GUIDE.md]

## 📊 Features Avançadas

### 🎮 Gamificação
- 6 tipos de badges (First Repair, Master, Fast Learner, etc)
- Sistema de pontos (XP) automático
- Leaderboard global + por curso
- Streak de atividade

### 📈 Analytics
- Receita total/mensal/anual
- Usuários novos/ativos
- Retenção Day 1/7/30/90
- Cohort Analysis (12 meses)

### 💰 Programa de Afiliados
- Código único por afiliado
- Comissões: 10% (nível 1), 5% (nível 2), 2% (nível 3)
- Payout automático
- Dashboard afiliado

## 🔒 Segurança

- ✅ JWT Authentication
- ✅ bcrypt password hashing
- ✅ CORS protection
- ✅ Rate Limiting
- ✅ SQL Injection Prevention (Eloquent)
- ✅ HTTPS (Let's Encrypt)
- ✅ OWASP Top 10 compliance

## 📖 Documentação Completa

- [`SETUP.md`](./SETUP.md) - Instalação local
- [`DEPLOYMENT_GUIDE.md`](./DEPLOYMENT_GUIDE.md) - Deploy VPS
- [`docs/DEPLOYMENT_COMPLETE_GUIDE.md`](./docs/DEPLOYMENT_COMPLETE_GUIDE.md) - Guia completo produção
- [`docs/openapi.yaml`](./docs/openapi.yaml) - API Swagger 3.0
- [`PROJETO_COMPLETO_RESUMO.txt`](./PROJETO_COMPLETO_RESUMO.txt) - Resumo técnico

## 🤝 Contribuindo

```bash
# Fork o repositório
# Crie uma branch feature
git checkout -b feature/minha-feature

# Faça commit
git commit -am 'Adicionar nova feature'

# Push para origin
git push origin feature/minha-feature

# Abra um Pull Request
```

## 📞 Suporte

- 📧 Email: suporte@escoladamanutencao.com.br
- 💬 Issues: GitHub Issues
- 📱 WhatsApp: (41) 3283-0558

## 📄 Licença

MIT License - veja [`LICENSE`](./LICENSE) para detalhes

---

## 🎯 Roadmap

- [ ] v2.0: Livestream de aulas
- [ ] v2.1: Mobile web (PWA)
- [ ] v2.2: Integração com ERP
- [ ] v2.3: Multi-tenant para franquias

---

**Feito com ❤️ em Paraná, Brasil** 🇧🇷
