# ✅ CHECKLIST DE INSTALAÇÃO - ESCOLA DA MANUTENÇÃO

**Tempo estimado**: 30-45 minutos  
**Dificuldade**: Fácil (com Docker)  
**Suporte**: Consulte INSTALACAO_COMPLETA.md

---

## 📥 PASSO 1: DOWNLOAD & EXTRAÇÃO (5 min)

### 1.1 Baixar Arquivos

```
☐ escola-manutencao-mvp-completo.zip     (137 KB)
☐ mega-pack-escola-manutencao.zip         (37 KB)
☐ live-streaming-youtube.zip              (25 KB)
☐ video-protection-auto-refresh.zip       (50 KB)
☐ INSTALACAO_COMPLETA.md                  (guia)
☐ setup.sh                                 (script)
```

### 1.2 Criar Pasta

```bash
☐ mkdir -p ~/Projetos/escola-manutencao
☐ cd ~/Projetos/escola-manutencao
```

### 1.3 Extrair ZIPs

```bash
☐ unzip escola-manutencao-mvp-completo.zip
☐ unzip mega-pack-escola-manutencao.zip
☐ unzip live-streaming-youtube.zip
☐ unzip video-protection-auto-refresh.zip
```

### 1.4 Verificar Estrutura

```bash
☐ ls -la | grep -E "docker-compose|app|resources"
☐ Deve ter: docker-compose.yml, app/, resources/, etc
```

---

## 🐳 PASSO 2: VERIFICAR DOCKER (5 min)

### 2.1 Instalar (se não tiver)

```bash
☐ docker --version
   (Se falhar, instalar: https://www.docker.com/products/docker-desktop)

☐ docker-compose --version
   (Se falhar, instalar: https://docs.docker.com/compose/install/)
```

### 2.2 Verificar Status

```bash
☐ docker ps
   (Deve listar containers, se houver algum)

☐ docker network ls
   (Verificar que Docker está funcionando)
```

---

## ⚡ PASSO 3: SETUP AUTOMÁTICO (10 min)

### 3.1 Executar Script

```bash
☐ cd ~/Projetos/escola-manutencao
☐ bash setup.sh

# Aguardar até ver: "🎉 SETUP CONCLUÍDO!"
# Tempo: ~5-10 minutos
```

### 3.2 Verificar Saída

Você deve ver:

```
✅ Docker instalado
✅ Containers rodando
✅ Database conectado
✅ Redis conectado
✅ Laravel configurado
✅ Assets compilados
🎉 SETUP CONCLUÍDO!
```

### 3.3 Se houver erro

```bash
☐ docker-compose logs laravel
   (Verificar logs do container)

☐ docker-compose logs postgres
   (Verificar se database está iniciando)

☐ Aguardar 30s e tentar novamente
   (Containers podem demorar para inicializar)
```

---

## 🌐 PASSO 4: ACESSAR APLICAÇÃO (5 min)

### 4.1 Website

```
☐ Abrir: http://localhost:8000
  (Deve mostrar landing page)

☐ Verificar logo, menu, etc
```

### 4.2 Admin Dashboard

```
☐ Abrir: http://localhost:8000/admin

☐ Login:
   Email:    admin@example.com
   Password: password

☐ Deve mostrar dashboard com 4 métricas
```

### 4.3 API Documentation

```
☐ Abrir: http://localhost:8000/api/docs

☐ Deve mostrar Swagger com endpoints:
   - POST /login
   - GET /courses
   - POST /repairs
   - etc
```

### 4.4 Database Admin (opcional)

```
☐ Abrir: http://localhost:8080 (PgAdmin)

☐ Login:
   Email:    admin@pgadmin.com
   Password: admin

☐ Explorar banco de dados
```

---

## 📱 PASSO 5: CONFIGURAR FLUTTER (10 min)

### 5.1 Dependências

```bash
☐ cd app
☐ flutter --version
   (Verificar que Flutter está instalado)

☐ flutter pub get
   (Instalar dependências)
```

### 5.2 Firebase

```bash
☐ flutterfire configure

☐ Selecionar plataforma: Android + iOS
   (Ou só Android se não tiver Mac)

☐ Deve gerar:
   ✓ android/app/google-services.json
   ✓ ios/GoogleService-Info.plist (se iOS)
```

### 5.3 Configurar Base URL

```
☐ Editar: lib/config/app_config.dart

☐ Mudar para:
   const String baseUrl = 'http://localhost:8000/api';

☐ Salvar
```

### 5.4 Executar App

```bash
☐ flutter run

☐ Selecionar device:
   - Chrome (web)
   - Android Emulator
   - iOS Simulator
   - Celular via USB

☐ App deve abrir e mostrar login
```

---

## 🧪 PASSO 6: TESTES BÁSICOS (10 min)

### 6.1 Login (Web)

```bash
☐ Abrir: http://localhost:8000/admin

☐ Dados de teste:
   Email:    admin@example.com
   Password: password

☐ Fazer login
☐ Deve mostrar dashboard
```

### 6.2 Login (App Flutter)

```bash
☐ App aberto no celular/emulador

☐ Tela de login:
   Email:    student@example.com
   Password: password

☐ Fazer login
☐ Deve mostrar home com cursos
```

### 6.3 Testar Curso

```bash
☐ App: Ir em "Cursos"
☐ Clicar em um curso
☐ Deve mostrar vídeos do curso
☐ Clicar em um vídeo
☐ Deve abrir player
☐ Verificar: play, pause, progresso
```

### 6.4 Testar Reparos

```bash
☐ App: Ir em "Reparos"
☐ Clicar em "Novo Reparo"
☐ Preencher formulário
☐ Tirar 4 fotos (ou usar câmera teste)
☐ Enviar
☐ Deve criar reparo
```

### 6.5 Testar Admin

```bash
☐ Web: Admin > Reparos
☐ Deve listar reparos criados
☐ Clicar em um reparo
☐ Aprovar ou rejeitar
☐ Deve salvar
```

---

## 💰 PASSO 7: CONFIGURAR CREDENCIAIS (15 min)

### 7.1 Mercado Pago

```bash
☐ Acessar: https://www.mercadopago.com.br/developers/panel

☐ Copiar:
   ☐ APP_USR_xxxx (public key)
   ☐ APP_USR_xxxx (secret key)

☐ Editar .env:
   MERCADO_PAGO_PUBLIC_KEY=APP_USR_xxxx
   MERCADO_PAGO_SECRET_KEY=APP_USR_xxxx

☐ Salvar
```

### 7.2 Firebase

```bash
☐ Acessar: https://console.firebase.google.com

☐ Criar projeto ou usar existente

☐ Copiar credenciais:
   ☐ Project ID
   ☐ Private Key ID
   ☐ Private Key
   ☐ Client Email
   ☐ Client ID

☐ Editar .env:
   FIREBASE_PROJECT_ID=seu-project
   FIREBASE_PRIVATE_KEY_ID=xxxx
   FIREBASE_PRIVATE_KEY="-----BEGIN..."
   FIREBASE_CLIENT_EMAIL=xxxx
   FIREBASE_CLIENT_ID=xxxx

☐ Salvar
```

### 7.3 Email SMTP (opcional agora)

```bash
☐ Para teste: usar Mailtrap.io (gratuito)

☐ Criar conta: https://mailtrap.io

☐ Copiar credenciais SMTP

☐ Editar .env:
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=465
   MAIL_USERNAME=seu_user
   MAIL_PASSWORD=seu_pass

☐ Salvar
```

---

## ✅ PASSO 8: VERIFICAÇÃO FINAL (5 min)

### 8.1 Backend

```bash
☐ Verificar status Docker:
   docker-compose ps
   (Todos "Up"?)

☐ Verificar logs:
   docker-compose logs -f laravel
   (Sem erros críticos?)

☐ Testar API:
   curl http://localhost:8000/api/health
   (Resposta: {"status":"ok"}?)
```

### 8.2 Database

```bash
☐ Verificar migrações:
   Abrir http://localhost:8080 (PgAdmin)
   Verificar tabelas criadas

☐ Verificar dados:
   SELECT COUNT(*) FROM users;
   (Deve ter usuários de teste)
```

### 8.3 App

```bash
☐ Login funcionando?
☐ Cursos aparecem?
☐ Vídeos reproduzem?
☐ Reparos criam?
☐ Notificações funcionam?
```

### 8.4 Segurança

```bash
☐ Admin password mudada?
   (de "password" para senha forte)

☐ JWT_SECRET alterado?
   (php artisan jwt:secret --force)

☐ DEBUG desativado em produção?
   (APP_DEBUG=false no .env)
```

---

## 🎉 PASSO 9: PRÓXIMOS PASSOS

### 9.1 Customização

```bash
☐ Alterar nome da app
☐ Adicionar seu logo
☐ Customizar cores (design system)
☐ Mudar textos para português
☐ Adicionar seus cursos reais
```

### 9.2 Testes

```bash
☐ Testar pagamento real (ou modo teste)
☐ Testar notificações push
☐ Testar certificados
☐ Testar gamificação
☐ Testar live streaming
```

### 9.3 Deploy

```bash
☐ Quando pronto:
   • Contratar VPS (DigitalOcean $24-48/mês)
   • Seguir: DEPLOYMENT_COMPLETE_GUIDE.md
   • Deploy automático com GitHub Actions
   • Setup SSL (Let's Encrypt gratuito)
   • Monitoramento 24/7
```

---

## 🆘 TROUBLESHOOTING

### Docker não inicia

```bash
☐ docker-compose down
☐ docker system prune -a
☐ docker-compose up -d
☐ Aguardar 30 segundos
```

### Database não conecta

```bash
☐ docker-compose logs postgres
☐ Verificar password em .env

☐ Reset (perder dados!):
   docker-compose down -v
   docker-compose up -d
   bash setup.sh
```

### App Flutter não conecta API

```bash
☐ Verificar .env: APP_URL = http://localhost:8000

☐ Editar lib/config/app_config.dart:
   const baseUrl = 'http://localhost:8000/api';

☐ flutter run --clean

☐ Se usar celular real:
   Mesmo network WiFi?
   IP correto (não localhost)?
```

### Porta 8000 já em uso

```bash
☐ Mudar em docker-compose.yml:
   ports:
     - "9000:80"  # Usar 9000

☐ Acessar: http://localhost:9000
```

---

## 📞 SUPORTE

| Problema | Arquivo |
|----------|---------|
| Instalação | INSTALACAO_COMPLETA.md |
| Deploy | DEPLOYMENT_COMPLETE_GUIDE.md |
| API docs | openapi.yaml |
| Features | MEGA_PACK_INDEX.md |
| Sistema | SISTEMA_COMPLETO_RESUMO.md |

---

## ✨ STATUS

Quando todos os itens ☐ estiverem marcados:

```
🎉 PARABÉNS!

Você tem uma plataforma profissional de cursos
100% funcional pronta para:
✅ Teste
✅ Customização
✅ Deploy em produção
✅ Lançamento
```

---

**Total de itens**: 40+  
**Tempo para completar**: ~45 minutos  
**Dificuldade**: ⭐⭐ (Fácil com Docker)

**Boa sorte! 🚀**
