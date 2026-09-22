# 🚀 Build APK - Escola da Manutenção v1.0.24

## ⚡ Quick Start (3 minutos)

```bash
cd flutter_app
flutter clean && flutter pub get
flutter build apk --release
```

**Arquivo:** `build/app/outputs/flutter-app/release/app-release.apk`

---

## 📋 Pré-requisitos

- [x] Flutter instalado (`flutter --version`)
- [x] Android SDK configurado
- [x] Java 11+ instalado

Se não tiver:
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

## 🔨 Passo a Passo

### 1. Clone
```bash
git clone https://github.com/pablogalindo159/escola-manutencao.git
cd escola-manutencao
```

### 2. Setup
```bash
cd flutter_app
flutter clean
flutter pub get
```

### 3. Build APK
```bash
flutter build apk --release
```

**Tempo:** ~5-10 minutos

### 4. Resultado
```
build/app/outputs/flutter-app/release/app-release.apk ✅
```

---

## 📱 Instalar no Telefone

### Via USB (recomendado)
```bash
flutter install
```

### Manual
1. Copiar APK para telefone
2. Abrir gerenciador de arquivos
3. Instalar

---

## 📤 Google Play Store

```bash
flutter build appbundle --release
# Arquivo: build/app/outputs/bundle/release/app-release.aab
```

Upload em: https://play.google.com/console

---

## ✅ Versão

**v1.0.24+24** - Webhook Mercado Pago Orders API funcional, PIX Transparente testado

---

**Pronto! APK será gerado em ~5-10 minutos.** 🎉

