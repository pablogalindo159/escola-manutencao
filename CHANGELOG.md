# Changelog - v1.0.24+24

**Data de Release:** 22 SET 2026  

## ✅ Webhook Mercado Pago Orders API - 100% FUNCIONAL!

### Fixes Implementados
- ✅ Webhook HMAC validando corretamente (Order ID lowercase)
- ✅ Status mapeando corretamente (processed → approved)
- ✅ Usuário sendo adicionado automaticamente ao curso
- ✅ Acesso liberado após pagamento aprovado

### Testes com Pagamento Real PIX ✅
- Webhook 1: action_required → pending
- Webhook 2: processed → approved (USUARIO ADICIONADO AO CURSO!)
- Webhook 3: action_required → pending

### Pronto para Produção
- ✅ HMAC SHA256 validando
- ✅ Conforme documentação oficial Mercado Pago
- ✅ Pagamento real testado
