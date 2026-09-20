# Bug Fix: Checkout Mercado Pago - 20 SET 2026

## Problema Identificado

**Sintoma:** Ao clicar em "Comprar" um curso pago, o usuário era redirecionado para `/cursos/4` em vez de ir para o Mercado Pago.

**Esperado:** Deveria redirecionar para URL de checkout do Mercado Pago (exemplo: `https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=...`)

**Status HTTP:** 302 Found

## Causa Raiz

Na `MercadoPagoService.php`, o método `createPreference()` estava tentando gerar URLs de retorno usando rotas que **não existiam**.

```php
// ERRADO (linhas 84-87):
'back_urls' => [
    'success' => route('checkout.return', ['status' => 'success']),
    'pending' => route('checkout.return', ['status' => 'pending']),
    'failure' => route('checkout.return', ['status' => 'failure']),
],
```

### Por que a rota não existia?

A rota estava definida em `routes/web.php` como:

```php
Route::middleware('auth')->prefix('minha-area')->name('student.')->group(function () {
    Route::get('/checkout/retorno', ...)->name('checkout.return');
});
```

**O problema:** Está dentro de um grupo com `name('student.')`, então o nome completo é `student.checkout.return`, não apenas `checkout.return`.

Quando `route('checkout.return')` era chamado, Laravel não encontrava a rota e jogava um erro: `Route [checkout.return] not defined`.

Isso causava que o Mercado Pago não conseguisse processar a preferência corretamente.

## Solução Implementada

### Commit: ddc61a0

**Arquivo:** `app/Services/MercadoPagoService.php`

**Mudança:**

```php
// CORRETO (linhas 84-87):
'back_urls' => [
    'success' => route('student.checkout.return', ['status' => 'success']),
    'pending' => route('student.checkout.return', ['status' => 'pending']),
    'failure' => route('student.checkout.return', ['status' => 'failure']),
],
```

## Validação

### Script de Debug Criado

Para debugar, foram criados:

1. **`debug/test.php`** — Script PHP standalone que:
   - Inicializa Laravel
   - Verifica credenciais Mercado Pago no banco
   - Busca um curso de teste
   - Tenta criar uma preference
   - Retorna o `init_point` (URL de checkout)

### Teste Executado (20 SET 2026, 19:38 UTC)

```bash
cd /var/www/escola-manutencao
php debug/test.php
```

**Output:**
```
===== MERCADO PAGO DEBUG =====

[1] Verificando credenciais no banco...
Access Token: APP_USR-371262664244...
Public Key: APP_USR-a19cd89e-d52...
Environment: sandbox

[2] Buscando dados de teste...
Curso: Manutençao de Notebooks (ID 1)
Preco: R$ 297.00
Usuario: Pablo Galindo

[3] Testando criacao de preference...
Preference ID: 3700131181-630cb91a-fa9a-4e95-ac28-b00c551dd2a4
Init Point: https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=3700131181-630cb91a-fa9a-4e95-ac28-b00c551dd2a4

SUCCESS! Checkout link gerado com sucesso!
```

✅ **Checkout funcionando!**

## Próximos Passos

### 1. Pull na VPS

```bash
cd /var/www/escola-manutencao
git pull origin main
```

### 2. Testar no Browser

Acessa o site:
```
https://escola.informaticasaojose.srv.br/minha-area/cursos/1
```

Clica em "Comprar" e verifica se redireciona para:
```
https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=...
```

### 3. Testar Fluxo Completo

1. Clicar "Comprar"
2. Preencher dados no Mercado Pago
3. Voltar para o site (success/pending)
4. Verificar se o webhook processa corretamente

## Commits Relacionados

### Debug
- **2a9268f** - debug: criar script PHP standalone para testar Mercado Pago (sem Tinker)
- **b64f741** - fix: recriar mercado-pago.php sem problemas de codificacao
- **dc87b9b** - fix: remover emojis que causam erro no Tinker
- **c528b4e** - fix: corrigir sintaxe para Tinker - remover return statements
- **f5f7c6a** - debug: adicionar scripts de debug para Mercado Pago

### Correção Principal
- **ddc61a0** - fix: corrigir nome da rota de retorno do checkout - student.checkout.return

## Lições Aprendidas

1. **Sempre considerar prefixos de grupo nas rotas** — Quando uma rota está dentro de um `Route::group()` com `name()`, o prefixo é automaticamente adicionado
2. **Usar script de debug standalone** — Tinker teve problemas de codificação, PHP direto foi mais confiável
3. **Testar integração antes do fluxo completo** — O script de debug isolou o problema rapidamente

## Status Atual

✅ **Checkout Mercado Pago: FUNCIONANDO**

Próximo: Testar fluxo completo de pagamento com webhook.
