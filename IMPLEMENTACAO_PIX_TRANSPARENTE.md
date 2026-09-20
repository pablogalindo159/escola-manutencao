# PIX Transparente - Implementação Completa

**Status:** ✅ Pronto para Deploy  
**Data:** 20 SET 2026  
**Versão:** 1.0

---

## O que é PIX Transparente?

Em vez de redirecionar o usuário para o site do Mercado Pago, o **PIX Transparente** exibe:
- ✅ QR Code do PIX
- ✅ Código PIX para copiar e colar
- ✅ Polling automático (verifica pagamento a cada 3 segundos)
- ✅ Timer de expiração (10 minutos)
- ✅ Tudo dentro do site - sem sair de nada!

---

## Arquivos Modificados

### 1. **app/Http/Controllers/Student/PaymentController.php**
- ❌ Removido: Redirecionamento para Checkout Pro
- ✅ Adicionado: Método `gerarPix()` - gera QR Code via API
- ✅ Adicionado: Método `statusPix()` - verifica status do pagamento
- ✅ Modificado: `checkout()` - agora renderiza view PIX ao invés de redirecionar

### 2. **resources/views/student/pix-transparente.blade.php** (NOVO)
- View com QR Code
- Código PIX copiável
- JavaScript para polling
- Timer countdown
- Instruções passo a passo

### 3. **routes/web.php**
Adicionadas 2 rotas:
```php
Route::post('/cursos/{course}/gerar-pix', ...)->name('courses.gerar-pix');
Route::get('/cursos/{course}/status-pix/{paymentId}', ...)->name('courses.status-pix');
```

---

## Fluxo de Pagamento

```
1. User clica "Comprar"
   ↓
2. POST /minha-area/cursos/{id}/checkout
   ↓
3. Renderiza view pix-transparente.blade.php
   ↓
4. JavaScript chama: POST /minha-area/cursos/{id}/gerar-pix
   ↓
5. Backend cria pagamento no MP e retorna QR Code
   ↓
6. View exibe QR Code + código PIX
   ↓
7. Polling a cada 3s: GET /minha-area/cursos/{id}/status-pix/{paymentId}
   ↓
8. User escaneia QR ou copia código no banco
   ↓
9. Paga no banco
   ↓
10. Polling detecta: status = "approved"
    ↓
11. Redirect para dashboard com sucesso ✅
```

---

## Como Funciona

### Frontend (View)

```javascript
// 1. Gera QR Code
POST /minha-area/cursos/{id}/gerar-pix
↓
Response: { payment_id, qr_code, copy_paste }

// 2. Exibe QR Code usando biblioteca qrcode.js
new QRCode(document.getElementById('qr-code'), { text: data.qr_code });

// 3. Polling a cada 3 segundos
GET /minha-area/cursos/{id}/status-pix/{paymentId}
↓
Response: { status: "approved" }
↓
Redirect para dashboard
```

### Backend (Controller)

```php
// POST /cursos/{course}/gerar-pix
1. Valida que o user não é aluno
2. Configura SDK Mercado Pago
3. Cria pagamento PIX na API MP
4. Salva Payment no BD
5. Retorna QR Code + código PIX

// GET /cursos/{course}/status-pix/{paymentId}
1. Consulta Mercado Pago
2. Retorna status do pagamento
3. Se approved: webhook processa e libera acesso
```

---

## Dependências

- ✅ `mercadopago/dx-php` (já no composer.json)
- ✅ `qrcode.js` (carregado via CDN)
- ✅ Laravel 11
- ✅ PHP 8.3

---

## Configuração Necessária

### 1. Credenciais Mercado Pago

Acesse: https://escola.informaticasaojose.srv.br/admin/configuracoes/mercado-pago

Preencha:
- Access Token (sandbox ou production)
- Public Key
- Webhook Secret

### 2. .env (opcional, se não usar painel admin)

```env
MERCADO_PAGO_ACCESS_TOKEN=APP_USR_...
MERCADO_PAGO_PUBLIC_KEY=APP_USR_...
MERCADO_PAGO_ENVIRONMENT=sandbox
```

---

## Deployment

### Na VPS

```bash
cd /var/www/escola-manutencao

# 1. Pull do git
git pull origin main

# 2. Instalar dependências (se não tiver)
composer install

# 3. Limpar cache
php artisan config:cache
php artisan view:clear

# 4. Restart PHP
systemctl restart php8.3-fpm
```

### Teste

```bash
# 1. Acessa site
https://escola.informaticasaojose.srv.br/minha-area/cursos/1

# 2. Clica "Comprar"
# Deveria exibir QR Code (não redirecionar para MP)

# 3. Testa com dados de teste do Mercado Pago
Cartão: 4111 1111 1111 1111
Vencimento: 11/25
CVV: 123

# 4. Verifica logs
tail -50 /var/www/escola-manutencao/storage/logs/laravel.log
```

---

## Estrutura da Resposta API

### POST /cursos/{course}/gerar-pix

```json
{
  "payment_id": "1234567890",
  "qr_code": "00020126580014br.gov.bcb.pix...",
  "qr_code_base64": "iVBORw0KGgoAAAANSUhEUgAAAAUA...",
  "copy_paste": "00020126580014br.gov.bcb.pix..."
}
```

### GET /cursos/{course}/status-pix/{paymentId}

```json
{
  "status": "approved",
  "approved": true
}
```

ou

```json
{
  "status": "pending",
  "approved": false
}
```

---

## Webhook

O webhook já está configurado no Mercado Pago:
- URL: `https://escola.informaticasaojose.srv.br/api/webhooks/mercadopago`
- Eventos: `payment.created`, `payment.updated`
- Ação: Atualiza Payment no BD e libera acesso ao curso

---

## Troubleshooting

### Erro: "Credenciais Mercado Pago não configuradas"

**Solução:**
1. Acesse `/admin/configuracoes/mercado-pago`
2. Preencha Access Token
3. Salve
4. Recarregue a página de checkout

### QR Code não aparece

**Solução:**
```bash
# Limpar cache
php artisan config:cache
php artisan view:clear
systemctl restart php8.3-fpm

# Recarregar página (CTRL+SHIFT+R)
```

### Polling não detecta pagamento

**Solução:**
1. Verificar se webhook está recebendo notificações
2. Consultar logs: `tail -50 storage/logs/laravel.log | grep -i "webhook\|payment"`
3. Verificar se user tem acesso: `php artisan tinker` → `\App\Models\User::find(1)->courses->count()`

---

## Commits Relacionados

```
6f70575 feat: implementar PIX Transparente com QR Code e polling
ddc61a0 fix: corrigir nome da rota de retorno do checkout
e3ac82c docs: documentar bug fix checkout Mercado Pago
```

---

## Próximos Passos (Futuro)

- [ ] Adicionar suporte a múltiplas tentativas de pagamento
- [ ] Integrar com Firebase para notificações push
- [ ] Dashboard admin com histórico de pagamentos
- [ ] Relatório de receita por curso
- [ ] Suporte a outros métodos (Boleto, Cartão)

---

## Support

Dúvidas? Verificar:
1. Logs: `/var/www/escola-manutencao/storage/logs/laravel.log`
2. Status Mercado Pago: https://status.mercadopago.com.br/
3. API Docs: https://www.mercadopago.com.br/developers/pt/docs/pix-transparency
