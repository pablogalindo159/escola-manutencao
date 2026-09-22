# 🚀 Escola da Manutenção

Plataforma educacional Flutter + Laravel para cursos de manutenção de celulares e computadores.

---

## ✅ Status

| Componente | Status |
|------------|--------|
| **Backend (Laravel)** | ✅ Produção |
| **App (Flutter)** | ✅ v1.0.24+24 |
| **Webhook PIX** | ✅ 100% Funcional |
| **Mercado Pago Orders API** | ✅ HMAC OK |

---

## 🔗 Links Importantes

- **Plataforma:** https://escola.informaticasaojose.srv.br
- **Releases:** https://github.com/pablogalindo159/escola-manutencao/releases
- **Build Workflow:** https://github.com/pablogalindo159/escola-manutencao/actions

---

## 📱 Build APK (Local)

### Pré-requisitos
```bash
flutter --version    # Instalar Flutter se não tiver
```

### Build em 3 linhas
```bash
cd flutter_app
flutter clean && flutter pub get
flutter build apk --release
```

**Arquivo:** `build/app/outputs/flutter-app/release/app-release.apk`

---

## 📥 Download Rápido

**Versão Atual:** v1.0.24+24

### 1️⃣ Clone
```bash
git clone https://github.com/pablogalindo159/escola-manutencao.git
cd escola-manutencao/flutter_app
```

### 2️⃣ Build
```bash
flutter clean && flutter pub get
flutter build apk --release
```

### 3️⃣ Instalar
```bash
# Conectar telefone via USB
flutter install

# Ou copiar manualmente:
# build/app/outputs/flutter-app/release/app-release.apk
```

---

## 🎯 Features

✅ Autenticação de usuários  
✅ Catálogo de cursos  
✅ Reprodução de vídeos  
✅ PIX Transparente (Mercado Pago)  
✅ Webhook com HMAC SHA256  
✅ Acesso automático após pagamento  
✅ Admin dashboard (Laravel)  

---

## 📊 Último Build

- **Versão:** v1.0.24+24
- **Data:** 22 SET 2026
- **Status:** ✅ Pronto para Produção

---

## 🔧 Stack

- **Frontend:** Flutter (Dart)
- **Backend:** Laravel 11 (PHP)
- **Database:** PostgreSQL
- **Pagamentos:** Mercado Pago Orders API
- **Deploy:** DigitalOcean VPS

---

## 📖 Documentação

- [BUILD-APK.md](./BUILD-APK.md) - Instruções detalhadas de build
- [CHANGELOG.md](./CHANGELOG.md) - Histórico de versões
- [Webhook Mercado Pago](./app/Http/Controllers/PaymentWebhookController.php) - Integração PIX

---

## 🎓 Webhook Mercado Pago

**Status:** ✅ 100% Operacional

```
POST /api/webhooks/mercadopago
Headers:
  - X-Signature: ts=..., v1=... (HMAC-SHA256)
  - X-Request-Id: ...

Response: HTTP 200 OK
```

Testes com pagamento real bem-sucedido ✅

---

## 📞 Suporte

Para dúvidas sobre build:
1. Consultar [BUILD-APK.md](./BUILD-APK.md)
2. Verificar [Releases](https://github.com/pablogalindo159/escola-manutencao/releases)
3. Abrir issue no GitHub

---

**Desenvolvido com ❤️ por Pablo Galindo**  
**Informática São José - São José dos Pinhais, PR**

