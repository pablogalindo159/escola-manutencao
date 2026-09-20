# 🐛 Debug Scripts - Escola da Manutenção

Scripts para debugar problemas da aplicação.

## Mercado Pago Debug

### Via Tinker (Recomendado)

```bash
cd /var/www/escola-manutencao
php artisan tinker
>>> include 'debug/mercado-pago.php'
```

**O que verifica:**
- ✅ Credenciais Mercado Pago no banco
- ✅ Se consegue buscar um curso+usuário de teste
- ✅ Se consegue chamar a API do Mercado Pago
- ✅ Se a resposta volta com `init_point` (URL de checkout)

### Via Rota HTTP (Alternativa)

Se você quer verificar via browser:

1. **Abra** `routes/web.php`
2. **Cole** isso ANTES de `return;` no arquivo:

```php
Route::get('/debug/mercado-pago', function () {
    return include resource_path('debug-mercado-pago-route.php');
});
```

3. Acesse: `https://escola.informaticasaojose.srv.br/debug/mercado-pago`

4. **DEPOIS**, remova a rota! ⚠️ Não deixa isso em produção!

---

## Diagnóstico

### Cenário 1: Access Token vazio
```
❌ ERRO: Access token não configurado no banco!
```
→ Vá para `/admin/configuracoes/mercado-pago` e salve as credenciais

### Cenário 2: Init Point vazio
```
❌ ERRO: init_point vazio!
```
→ Resposta da API do MP está incompleta. Pode ser:
- Payload malformado
- Credencial inválida
- Rate limit atingido

### Cenário 3: Erro 401/403
```
❌ ERRO: ... 401 Unauthorized
```
→ Access token inválido ou expirado. Regenere no painel do Mercado Pago.

### Cenário 4: Timeout
```
❌ ERRO: Connection timeout
```
→ Firewall bloqueando `api.mercadopago.com`. Verifique configurações de rede.

---

## Logs

Se o debug não funcionar, cheque os logs:

```bash
tail -50 /var/www/escola-manutencao/storage/logs/laravel.log | grep -i "mercado\|payment\|error"
```

---

## Status Actual

**Problema:** Checkout redireciona para `/cursos/4` em vez do Mercado Pago

**Localização:** `app/Http/Controllers/Student/PaymentController.php` linha 43

**Próxima ação:** Rode o debug acima e manda o resultado!
