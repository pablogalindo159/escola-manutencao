# ⚡ Build APK - Quick Start

## 3 Linhas - 10 Minutos

```bash
cd flutter_app
flutter clean && flutter pub get
flutter build apk --release
```

**Arquivo gerado:** `build/app/outputs/flutter-app/release/app-release.apk`

---

## ✅ Pré-requisitos

- [x] Flutter instalado (`flutter --version`)
- [x] Android SDK (vem com Flutter)
- [x] Java 11+

**Se não tiver Flutter:**
```bash
# macOS
brew install flutter

# Windows
choco install flutter

# Linux
git clone https://github.com/flutter/flutter.git -b stable ~/flutter
export PATH="$PATH:$HOME/flutter/bin"
```

---

## 📱 Instalar no Telefone

### Via USB
```bash
flutter install
```

### Manual
1. Copiar `app-release.apk` para telefone
2. Abrir com gerenciador de arquivos
3. Instalar

---

## 🎯 Status

- **Versão:** v1.0.24+24 ✅
- **Webhook:** Mercado Pago OK ✅
- **PIX:** Testado com pagamento real ✅
- **Pronto:** Produção ✅

---

## 📥 Download Rápido

```bash
# 1. Clone
git clone https://github.com/pablogalindo159/escola-manutencao.git
cd escola-manutencao

# 2. Build
cd flutter_app
flutter clean && flutter pub get
flutter build apk --release

# 3. APK está em:
# build/app/outputs/flutter-app/release/app-release.apk
```

---

**Pronto! APK gerado em ~10 minutos.** 🚀
