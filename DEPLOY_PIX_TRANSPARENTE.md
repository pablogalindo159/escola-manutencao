# 🎯 PIX TRANSPARENTE - DEPLOY AUTOMÁTICO (20 SET 2026)

## Status: Pronto pra executar na VPS

### ✅ Checklist Pré-Deploy
- [x] SDK Mercado Pago adicionado ao composer.json
- [x] PixTransparenteController corrigido (campos válidos)
- [x] Rotas PIX integradas em routes/api.php
- [x] Credenciais Mercado Pago configuradas
- [x] Migrations pronta
- [x] Webhook endpoint pronto

### 🚀 Comando Deploy Automático

Executar na VPS:
```bash
ssh root@198.199.64.162 << 'DEPLOY'
cd /var/www/escola-manutencao
git pull origin main
composer require mercadopago/dx-php
php artisan migrate --force
php artisan config:cache
php artisan route:cache
systemctl restart php8.3-fpm
echo "✅ Deploy concluído!"
DEPLOY
```

### 🔧 Credenciais (já configuradas no .env)
```
MERCADO_PAGO_PUBLIC_KEY=APP_USR-c8b7b9d6-14fa-463d-a526-9a78fcad33a3
MERCADO_PAGO_ACCESS_TOKEN=APP_USR-7095060589620910-060919-ac49c5e4113b6f107345c6e9d3d6dcc4-476784116
MERCADO_PAGO_WEBHOOK_SECRET=ff82970cce3fff81da175b569787e4bae70b26293b8ecad0b783b83074096bc6
```

### 📋 Testes Pós-Deploy

```bash
# 1. Pegar token admin
TOKEN=$(curl -s -X POST https://escola.informaticasaojose.srv.br/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"Senha123!"}' | jq -r '.access_token')

# 2. Gerar PIX
curl -X POST https://escola.informaticasaojose.srv.br/api/payments/pix/gerar \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"course_id":1,"amount":1.00,"description":"Teste"}'

# 3. Esperado: JSON com "success": true e qr_code
```

### 📅 Timeline
- Deploy: 5 minutos
- Testes: 10 minutos
- **Total: ~15 minutos**

---

**Próximo passo:** Executar deployment na VPS e validar com curl tests.
