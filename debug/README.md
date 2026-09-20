# Debug Scripts - Escola da Manutencao

Scripts para debugar problemas da aplicacao.

## Mercado Pago Debug

### Via PHP direto (Recomendado)

```bash
cd /var/www/escola-manutencao
php debug/test.php
```

**O que verifica:**
- Credenciais Mercado Pago no banco
- Se consegue buscar um curso+usuario de teste
- Se consegue chamar a API do Mercado Pago
- Se a resposta volta com init_point (URL de checkout)

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
