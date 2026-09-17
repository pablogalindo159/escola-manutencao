# 🎓 Plataforma Escola da Manutenção - Backend Laravel

Plataforma completa de cursos em vídeo para a **Escola da Manutenção** desenvolvida em **Laravel** com banco de dados PostgreSQL.

## ✨ Features

- ✅ **API REST completa** com autenticação JWT
- ✅ **Cursos e vídeos** com progresso de aluno
- ✅ **"Meus Reparos"** - Sistema especial para registrar diagnósticos
- ✅ **Comunidade** - Posts, comentários, likes
- ✅ **Certificados** - Geração automática com QR Code
- ✅ **Pagamentos** - Integração Mercado Pago
- ✅ **Notificações** - Sistema de notificações por email
- ✅ **Admin Panel** - Gerenciamento completo
- ✅ **Segurança** - JWT, Rate Limiting, CORS
- ✅ **Cache** - Redis para performance
- ✅ **Banco de dados** - PostgreSQL com migrations

## 🏗️ Arquitetura

```
Backend Laravel (API REST)
├── Autenticação (JWT via Tymon/jwt-auth)
├── Modelos de Dados (11 tabelas)
├── Controllers (Lógica de negócio)
├── Middlewares (Autenticação, CORS, Rate Limiting)
├── Migrations (Versionamento BD)
└── Routes (40+ endpoints)

Banco: PostgreSQL
Cache: Redis
Pagamentos: Mercado Pago
Armazenamento: AWS S3 (opcional)
```

## 📋 Modelos de Dados

1. **User** - Usuários (aluno, professor, admin)
2. **Course** - Cursos
3. **Video** - Vídeos do curso
4. **UserProgress** - Progresso do aluno
5. **Subscription** - Assinatura/compra de curso
6. **Payment** - Pagamentos via Mercado Pago
7. **Post** - Posts da comunidade
8. **Comment** - Comentários nos posts
9. **Repair** - Reparos/diagnósticos ("Meus Reparos")
10. **RepairPhoto** - Fotos dos reparos (antes/durante/depois)
11. **Certificate** - Certificados de conclusão

## 🚀 Quick Start

### Pré-requisitos
- Docker e Docker Compose instalados
- Git
- 1GB de RAM disponível

### Instalação

```bash
# 1. Clone o repositório
git clone <seu-repo>
cd escola-manutencao-backend

# 2. Copie o arquivo de ambiente
cp .env.example .env

# 3. Gere a chave da aplicação
docker-compose run --rm app php artisan key:generate

# 4. Gere a secret do JWT
docker-compose run --rm app php artisan jwt:secret

# 5. Inicie os containers
docker-compose up -d

# 6. Rode as migrations (automático no startup)
docker-compose exec app php artisan migrate

# 7. Crie um usuário admin (opcional)
docker-compose exec app php artisan tinker
# User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'role' => 'admin'])
```

### Acessar

- **API**: http://localhost:8000
- **PgAdmin**: http://localhost:5050
  - Email: admin@example.com
  - Senha: admin

## 📡 Endpoints da API

### Autenticação
```
POST   /api/auth/register        - Registrar novo usuário
POST   /api/auth/login           - Login
POST   /api/auth/refresh-token   - Renovar token JWT
POST   /api/auth/logout          - Logout
```

### Perfil do Usuário
```
GET    /api/profile              - Obter perfil
PUT    /api/profile              - Atualizar perfil
```

### Cursos
```
GET    /api/courses              - Listar cursos
GET    /api/courses/{id}         - Detalhes do curso
GET    /api/courses/{id}/videos  - Vídeos do curso
```

### Vídeos
```
GET    /api/videos/{id}          - Detalhes do vídeo
POST   /api/videos/{id}/progress - Atualizar progresso
```

### Progresso
```
GET    /api/progress/{courseId}  - Progresso no curso
```

### "Meus Reparos"
```
POST   /api/repairs              - Criar reparo
GET    /api/repairs              - Listar reparos
GET    /api/repairs/{id}         - Detalhes
POST   /api/repairs/{id}/photos  - Upload de foto
PUT    /api/repairs/{id}         - Atualizar
POST   /api/repairs/{id}/submit  - Enviar para análise
```

### Comunidade
```
GET    /api/posts/{courseId}     - Posts do curso
POST   /api/posts                - Criar post
POST   /api/posts/{id}/like      - Curtir post
GET    /api/comments/{postId}    - Comentários
POST   /api/comments             - Criar comentário
```

### Certificados
```
GET    /api/certificates         - Meus certificados
GET    /api/certificates/{id}/download - Download PDF
GET    /api/verify/{number}      - Verificar certificado
```

### Pagamentos
```
POST   /api/payments/create-preference - Criar preferência Mercado Pago
POST   /api/webhooks/mercado-pago - Webhook de pagamento
```

### Admin
```
GET    /api/admin/users          - Listar usuários
GET    /api/admin/analytics      - Analytics
POST   /api/admin/courses        - Criar curso
PUT    /api/admin/courses/{id}   - Editar curso
```

## 🔑 Variáveis de Ambiente

Edite o arquivo `.env`:

```env
# Database
DB_DATABASE=escola_manutencao
DB_USERNAME=postgres
DB_PASSWORD=postgres

# JWT
JWT_SECRET=<gerado automaticamente>
JWT_TTL=60

# Email (Mailtrap/SendGrid)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=<seu-username>
MAIL_PASSWORD=<sua-senha>

# Mercado Pago
MERCADO_PAGO_ACCESS_TOKEN=<seu-token>

# AWS S3 (opcional)
AWS_ACCESS_KEY_ID=<sua-chave>
AWS_SECRET_ACCESS_KEY=<sua-secret>
AWS_BUCKET=escola-manutencao
```

## 📦 Estrutura de Pastas

```
app/
├── Models/              - Modelos Eloquent
├── Http/
│   ├── Controllers/     - Controllers da API
│   ├── Requests/        - Form Requests (validação)
│   └── Middleware/      - Middlewares
├── Exceptions/          - Exceções customizadas
└── Traits/              - Traits reutilizáveis

database/
├── migrations/          - Migrations do BD
└── seeders/             - Seeders (dados iniciais)

routes/
└── api.php              - Rotas da API

config/
├── app.php              - Config da app
├── database.php         - Config do BD
└── jwt.php              - Config do JWT
```

## 🔐 Segurança

- ✅ Senhas hasheadas com bcrypt
- ✅ JWT para autenticação stateless
- ✅ CORS configurado
- ✅ Rate limiting (60 req/min por padrão)
- ✅ SQL Injection protected (Eloquent ORM)
- ✅ CSRF protection
- ✅ Helmet headers

## 📊 Banco de Dados

Conexão PostgreSQL com as seguintes tabelas:
- users (autenticação)
- courses (cursos)
- videos (vídeos)
- user_progress (progresso)
- subscriptions (assinaturas)
- payments (pagamentos)
- posts (comunidade)
- comments (comentários)
- repairs (meus reparos)
- repair_photos (fotos)
- certificates (certificados)
- post_likes, comment_likes (pivot tables)

## 🧪 Testes

```bash
# Rodar testes unitários
docker-compose exec app php artisan test

# Rodar com coverage
docker-compose exec app php artisan test --coverage
```

## 📚 Documentação Adicional

- [Laravel Docs](https://laravel.com/docs)
- [JWT Auth](https://jwt.io/)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)
- [Mercado Pago API](https://developers.mercadopago.com/)

## 🤝 Deploy

### Heroku
```bash
heroku create seu-app-name
git push heroku main
heroku run php artisan migrate
```

### DigitalOcean
1. Create App Platform app
2. Connect GitHub
3. Set build command: `composer install`
4. Set run command: `php artisan serve --host=0.0.0.0 --port=8080`
5. Add PostgreSQL database
6. Deploy

### AWS EC2
```bash
# SSH into instance
ssh ec2-user@your-instance

# Install dependencies
sudo yum install php php-pdo php-pgsql composer

# Clone and setup
git clone <repo>
cd escola-manutencao-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force

# Start with PM2 or supervisor
```

## 📝 Logs

```bash
# Ver logs em tempo real
docker-compose logs -f app

# Ver logs específicos
docker-compose logs app | grep "error"
```

## 🆘 Troubleshooting

### Erro de conexão com banco
```bash
# Verificar se postgres está rodando
docker-compose ps

# Reiniciar containers
docker-compose restart
```

### Erro JWT
```bash
# Regenerar JWT secret
docker-compose exec app php artisan jwt:secret
```

### Storage permissions
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

## 📞 Contato

- Email: dev@escoladamanutencao.com.br
- Website: escoladamanutencao.com.br

## 📄 Licença

Proprietary © Escola da Manutenção
