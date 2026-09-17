# 🚀 Guia Completo de Setup - Escola da Manutenção Backend

## Fase 1: Setup Local (Desenvolvimento)

### Passo 1: Pré-requisitos
```bash
# Verificar se tem Docker
docker --version
docker-compose --version

# Se não tiver, instale de: https://www.docker.com/products/docker-desktop
```

### Passo 2: Preparar o Projeto
```bash
# Clone do GitHub (ou copie os arquivos)
git clone <seu-repositorio>
cd escola-manutencao-backend

# Verifique se tem estes arquivos:
ls -la | grep -E "(docker-compose|Dockerfile|composer.json|package.json|.env.example)"
```

### Passo 3: Configurar Ambiente
```bash
# Copie o arquivo de ambiente
cp .env.example .env

# IMPORTANTE: Abra o .env e preencha:
# - DB_PASSWORD (crie uma senha)
# - JWT_SECRET (será gerado)
# - MERCADO_PAGO_ACCESS_TOKEN (depois)
# - MAIL_* (configurar depois)
```

### Passo 4: Iniciar Docker
```bash
# Suba os containers
docker-compose up -d

# Aguarde 10-15 segundos para banco inicializar
sleep 15

# Verifique se está tudo rodando
docker-compose ps
# Deve mostrar: postgres, redis, app, pgadmin COMO "Up"
```

### Passo 5: Instalar Dependências
```bash
# Laravel dependencies (PHP)
docker-compose exec app composer install

# Node dependencies (CSS/JS)
docker-compose exec app npm install
docker-compose exec app npm run build
```

### Passo 6: Gerar Chaves
```bash
# Chave da aplicação
docker-compose exec app php artisan key:generate

# JWT Secret
docker-compose exec app php artisan jwt:secret

# Verifique no .env se tem JWT_SECRET preenchido
docker-compose exec app grep JWT_SECRET .env
```

### Passo 7: Rodar Migrations
```bash
# Criar todas as tabelas no banco
docker-compose exec app php artisan migrate

# Se tudo correr bem, verá:
# ✓ Migration table created successfully
# ✓ Migrating: 2024_01_01_000001_create_users_table
# ... mais migrations ...
# ✓ Batch 1 completed
```

### Passo 8: Criar Usuário Admin (Opcional)
```bash
# Entre no tinker (REPL do Laravel)
docker-compose exec app php artisan tinker

# Digite no prompt:
User::create([
    'name' => 'Admin Escola',
    'email' => 'admin@escoladamanutencao.com.br',
    'password' => Hash::make('senha_super_segura_123'),
    'role' => 'admin',
    'status' => 'active',
    'cpf' => '12345678901',
    'phone' => '41999999999'
])

# Saia com: exit
```

### Passo 9: Testar API
```bash
# Terminal 1: Ver logs
docker-compose logs -f app

# Terminal 2: Testar login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@escoladamanutencao.com.br",
    "password": "senha_super_segura_123"
  }'

# Deve retornar algo como:
# {
#   "success": true,
#   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "token_type": "Bearer",
#   "expires_in": 3600
# }
```

### Passo 10: Acessar PgAdmin (Banco Visual)
```
URL: http://localhost:5050
Email: admin@example.com
Senha: admin

Depois:
1. Clique em "Add New Server"
2. Name: escola_manutencao
3. Connection tab:
   - Host: postgres
   - Port: 5432
   - Maintenance database: postgres
   - Username: postgres
   - Password: <sua-senha-do-.env>
4. Save
```

---

## ✅ Checklist: Pronto Para Desenvolver?

- [ ] Docker rodando (`docker-compose ps` mostra tudo "Up")
- [ ] Migrations rodadas (`docker-compose exec app php artisan migrate:status`)
- [ ] Login funciona (POST /api/auth/login retorna token)
- [ ] PgAdmin acessível (http://localhost:5050)
- [ ] Node dependencies instaladas (`node_modules` existe)

---

## 🔧 Comandos Úteis Diários

```bash
# Ver logs em tempo real
docker-compose logs -f app

# Executar comando no container
docker-compose exec app <comando>

# Exemplo: Rodar seeder
docker-compose exec app php artisan db:seed

# Exemplo: Acessar tinker
docker-compose exec app php artisan tinker

# Exemplo: Criar migration nova
docker-compose exec app php artisan make:migration create_<table_name>_table

# Parar containers
docker-compose stop

# Iniciar novamente
docker-compose start

# Remover tudo (CUIDADO!)
docker-compose down -v  # -v remove volumes de BD também
```

---

## 📝 Fase 2: Desenvolvimento

Agora que tudo está rodando:

### Criar um Controller novo
```bash
docker-compose exec app php artisan make:controller Api/CoursesController
```

### Criar um Model novo
```bash
docker-compose exec app php artisan make:model Course -m  # -m cria migration também
```

### Criar uma Request (Validação)
```bash
docker-compose exec app php artisan make:request StoreCourseRequest
```

### Testar endpoint com cURL
```bash
curl -X GET http://localhost:8000/api/courses \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### Usar o Postman
1. Baixe Postman de: https://www.postman.com/downloads/
2. Importe a collection (será criada depois)
3. Use a variável `{{token}}` para autenticação

---

## 🚨 Troubleshooting

### "Connection refused" ao conectar com banco
```bash
# O postgres demora para iniciar
docker-compose logs postgres

# Reinicie se necessário
docker-compose restart postgres
docker-compose restart app
```

### "SQLSTATE[08006]" - Database error
```bash
# Verifique credenciais no .env
docker-compose exec app grep DB_ .env

# Confirm que postgres está rodando
docker-compose exec postgres psql -U postgres -l
```

### "JWT_SECRET not set"
```bash
# Regenere
docker-compose exec app php artisan jwt:secret

# Verifique
docker-compose exec app grep JWT_SECRET .env
```

### Container saindo sozinho
```bash
# Veja o erro
docker-compose logs app

# Se composer installation failed
docker-compose up -d --build  # Force rebuild
```

### Espaço em disco cheio
```bash
# Limpe containers parados
docker container prune

# Limpe imagens não usadas
docker image prune

# Ou tudo
docker system prune -a
```

---

## 📊 Fase 3: Testando a API

Use o Postman ou cURL para testar:

### 1. Registrar usuário
```
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "senha123",
  "phone": "41999999999",
  "cpf": "12345678901"
}
```

### 2. Fazer login
```
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "joao@example.com",
  "password": "senha123"
}

# Resposta: {access_token, token_type, expires_in}
# Salve o access_token para usar em outros requests
```

### 3. Usar token em outras requisições
```
GET http://localhost:8000/api/profile
Authorization: Bearer <seu-access-token>
```

---

## 🎬 Próximos Passos

Agora você tem:
✅ Backend Laravel rodando localmente
✅ PostgreSQL com todas as tabelas
✅ Autenticação JWT funcionando
✅ API REST pronta para desenvolver

### Próximo: 
1. **Criar Controllers** para cada Model
2. **Implementar lógica de negócio** nos Controllers
3. **Testar endpoints** um por um
4. **Criar App Flutter** que consome essa API
5. **Criar Website** (Blade/React) que consome essa API

---

## 📚 Referências

- Laravel Docs: https://laravel.com/docs/11.x
- JWT Auth: https://github.com/tymondesigns/jwt-auth
- PostgreSQL: https://www.postgresql.org/
- Docker: https://docs.docker.com/
- Postman: https://learning.postman.com/

---

**Tudo funcionando?** 🎉  
Vamos para a **Fase 2: Implementar os Controllers e a lógica da API!**
