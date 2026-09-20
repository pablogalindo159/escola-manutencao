# Alternar entre PIX Transparente e Checkout Pro

**Status:** ✅ Implementado  
**Data:** 20 SET 2026  
**Versão:** 2.0

---

## O que mudou?

Agora você tem **DUAS OPÇÕES** de método de pagamento e pode alternar entre elas **quando quiser** no painel admin:

### ✅ PIX Transparente (PADRÃO)
- QR Code aparece no site
- User **não sai** do site
- Polling automático
- Melhor UX

### ✅ Checkout Pro
- User redireciona para site do Mercado Pago
- Método tradicional
- Mais conhecido

---

## Como Usar

### 1️⃣ Acessar Painel Admin

```
https://escola.informaticasaojose.srv.br/admin/configuracoes/mercado-pago
```

### 2️⃣ Preencher Credenciais (se não tiver)

- ✅ Access Token
- ✅ Public Key
- ✅ Webhook Secret
- ✅ Ambiente (sandbox ou production)

### 3️⃣ **NOVO**: Selecionar Método de Pagamento

```
💳 Método de Pagamento
  ○ 📱 PIX Transparente — QR Code no site (RECOMENDADO)
  ○ 🔗 Checkout Pro — Redireciona para Mercado Pago
```

### 4️⃣ Salvar

Clique em "✅ Salvar Configurações"

---

## O que Acontece Depois

### Se escolher PIX Transparente:

```
User clica "Comprar"
    ↓
Vê QR Code no site (não sai de nada)
    ↓
Escaneia com celular
    ↓
Paga no banco
    ↓
Site detecta automaticamente ✅
    ↓
Redireciona para dashboard
```

### Se escolher Checkout Pro:

```
User clica "Comprar"
    ↓
Redireciona para mercadopago.com.br
    ↓
Paga lá
    ↓
Volta para o site
    ↓
Webhook processa ✅
```

---

## Quando Trocar?

### Use PIX Transparente (📱) quando:
- ✅ Quer melhor UX
- ✅ Quer que user fica no site
- ✅ PIX é seu método principal
- ✅ Está em Sandbox (testes)

### Use Checkout Pro (🔗) quando:
- ✅ Quer suportar cartão de crédito
- ✅ Quer parcelamento
- ✅ Quer método padrão do Mercado Pago
- ✅ Já usa em produção e funciona bem

---

## Arquivo Changed

### Na VPS

```bash
cd /var/www/escola-manutencao
git pull origin main

# Rodar migration nova
php artisan migrate

# Limpar cache
php artisan config:cache
php artisan view:clear

# Restart
systemctl restart php8.3-fpm
```

### Arquivos Modificados

1. **app/Http/Controllers/Admin/SettingsController.php**
   - Adicionado campo `payment_method`
   - Validação incluída

2. **resources/views/admin/settings/mercado-pago.blade.php**
   - Select para escolher método
   - Status mostra qual está ativo

3. **app/Http/Controllers/Student/PaymentController.php**
   - Método `checkout()` agora verifica qual usar
   - Novo método `checkoutPro()` para Checkout Pro

4. **database/migrations/2026_09_20_201500_add_payment_method_to_settings.php** (NEW)
   - Migration para adicionar padrão

---

## Fluxo Técnico

```
User clica "Comprar"
    ↓
POST /minha-area/cursos/{id}/checkout
    ↓
StudentPaymentController::checkout()
    ↓
Setting::get('mercado_pago_payment_method')
    ↓
    ├─ pix_transparente → view('pix-transparente')
    │
    └─ checkout_pro → checkoutPro() → redirect().away(init_point)
```

---

## Status Atual no Admin

O painel agora mostra:

```
📊 Status Atual

Ambiente: 🧪 Sandbox
Método de Pagamento: 📱 PIX Transparente (Ativo)
Access Token: ✅ Configurado
Public Key: ✅ Configurado
Webhook Secret: ✅ Configurado
```

---

## Troubleshooting

### "Painel não mostra o seletor"

**Solução:**
```bash
php artisan config:cache
php artisan view:clear
systemctl restart php8.3-fpm
# Recarregar página (CTRL+SHIFT+R)
```

### "Clicou em Comprar mas não aparece QR Code"

**Solução:**
1. Verificar qual método está ativo no painel
2. Verificar logs: `tail -50 storage/logs/laravel.log`
3. Certificar que credenciais estão salvas

### "Quer trocar de método"

**Solução:**
1. Acesse `/admin/configuracoes/mercado-pago`
2. Mude o select
3. Clique "Salvar"
4. Pronto! Já usa o novo método

---

## Commits

```
2df06f8 feat: adicionar seletor de método de pagamento no painel admin
6f70575 feat: implementar PIX Transparente com QR Code e polling
ddc61a0 fix: corrigir nome da rota de retorno do checkout
```

---

## Suporte

Dúvidas? Verificar:
1. `/admin/configuracoes/mercado-pago` (painel admin)
2. Logs: `tail -50 storage/logs/laravel.log`
3. Documentação: `IMPLEMENTACAO_PIX_TRANSPARENTE.md`
