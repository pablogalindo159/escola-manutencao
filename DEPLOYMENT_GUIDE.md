# 🚀 DEPLOYMENT GUIDE - Escola da Manutenção

**Status**: ✅ Pronto para Deploy  
**Data**: 2026-09-16  
**Versão**: 1.0.0

---

## 📋 Pré-Requisitos

### Android
- [ ] Android Studio 2023+
- [ ] Android SDK 21+ (min) / 34 (target)
- [ ] Java 11+
- [ ] Gradle 7.5+
- [ ] Chave de assinatura (keystore)

### iOS
- [ ] Xcode 14+
- [ ] iOS 12.0+ (min)
- [ ] CocoaPods
- [ ] Apple Developer Account
- [ ] Certificado de assinatura

### Geral
- [ ] Flutter 3.10+
- [ ] Dart 3.0+
- [ ] Git
- [ ] Firebase Console Account
- [ ] Mercado Pago Account

---

## 🔐 1. Configuração de Credenciais

### 1.1 Firebase Setup

```bash
# Instalar FlutterFire CLI
dart pub global activate flutterfire_cli

# Configurar Firebase (interativo)
flutterfire configure

# Isso irá:
# - Criar projeto no Firebase Console
# - Gerar google-services.json (Android)
# - Gerar GoogleService-Info.plist (iOS)
# - Atualizar pubspec.yaml
```

### 1.2 Mercado Pago Credentials

**No arquivo `.env` ou variáveis de ambiente:**

```env
MERCADO_PAGO_PUBLIC_KEY=APP_USR_xxxxxxxxxxxxxxxxxxxxxxxx
MERCADO_PAGO_ACCESS_TOKEN=APP_USR_xxxxxxxxxxxxxxxxxxxxxxxx
MERCADO_PAGO_ENVIRONMENT=PRODUCTION  # SANDBOX para testes
```

**Obter em:** https://www.mercadopago.com.br/developers/panel/credentials

---

## 🔑 2. Assinatura Digital (Android)

### 2.1 Criar Keystore

```bash
# Gerar keystore
keytool -genkey -v -keystore ~/android_keystore/escola_keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias escola_key \
  -storepass YOUR_STORE_PASSWORD \
  -keypass YOUR_KEY_PASSWORD

# Guardar em local seguro!
```

### 2.2 Configurar Gradle

Criar arquivo `android/key.properties`:

```properties
storeFile=/Users/pablo/.android/escola_keystore.jks
storePassword=YOUR_STORE_PASSWORD
keyAlias=escola_key
keyPassword=YOUR_KEY_PASSWORD
```

---

## 📱 3. Build para Android

### 3.1 Debug Build

```bash
flutter build apk --debug
# Saída: build/app/outputs/flutter-apk/app-debug.apk
```

### 3.2 Release Build

```bash
# Limpar builds anteriores
flutter clean

# Build optimizado
flutter build apk \
  --release \
  --obfuscate \
  --split-debug-info=build/app/outputs/symbols

# Saída: build/app/outputs/flutter-apk/app-release.apk
```

### 3.3 App Bundle (Play Store)

```bash
# Build otimizado para Play Store
flutter build appbundle --release

# Saída: build/app/outputs/bundle/release/app-release.aab
```

---

## 🍎 4. Build para iOS

### 4.1 Preparação

```bash
# Atualizar pods
cd ios
pod install
cd ..

# Aceitar Xcode license
xcode-select --install
sudo xcode-select --switch /Applications/Xcode.app/Contents/Developer
```

### 4.2 Build

```bash
# Debug (simulator)
flutter build ios --debug --simulator

# Release (device)
flutter build ios --release

# Saída: build/ios/iphoneos/Runner.app
```

### 4.3 Build para App Store

```bash
# No Xcode ou via CLI
flutter build ios --release
# Depois: Product → Archive → Distribute App → App Store
```

---

## 🎯 5. Play Store Upload

### 5.1 Preparar

1. Criar conta Google Play Developer (R$ 25)
2. Aceitar termos e políticas
3. Criar aplicação na console

### 5.2 Preencher Informações

- [ ] Nome do app
- [ ] Descrição curta (80 caracteres)
- [ ] Descrição completa
- [ ] Categorias
- [ ] Ícone (512x512, PNG)
- [ ] Screenshots (mín. 2)
- [ ] Email de contato
- [ ] Política de privacidade (URL)
- [ ] Site da aplicação

### 5.3 Upload

```bash
# Instalar bundletool (opcional)
wget https://github.com/google/bundletool/releases/latest/download/bundletool.jar

# Upload via Google Play Console:
# 1. Acessar https://play.google.com/console
# 2. Selecionar app
# 3. Testing → Closed testing (ou Internal testing)
# 4. Upload AAB (build/app/outputs/bundle/release/app-release.aab)
# 5. Preencher release notes
# 6. Revisar
# 7. Publicar em produção
```

---

## 🍎 6. App Store Upload

### 6.1 Preparar

1. Criar Apple Developer Account (R$ 99/ano)
2. Criar Bundle ID (com.escoladamanutencao.app)
3. Criar App ID na App Store Connect
4. Criar certificados de assinatura

### 6.2 Preencher Informações

- [ ] Nome
- [ ] Descrição
- [ ] Palavras-chave
- [ ] Categoria
- [ ] Classificação etária
- [ ] Screenshots (6.5": 1284x2778)
- [ ] Preview video (opcional)
- [ ] Email de contato

### 6.3 Upload

```bash
# Via Xcode:
# 1. Abrir Xcode project
# 2. Select Team (certificado)
# 3. Product → Archive
# 4. Distribute App → App Store Connect
# 5. Preencher informações
# 6. Upload

# Ou via linha de comando:
# xcodebuild archive -workspace ios/Runner.xcworkspace \
#   -scheme Runner \
#   -archivePath build/ios/archive.xcarchive
```

---

## 🧪 7. Testes Pré-Deploy

### 7.1 Testes Locais

```bash
# Executar testes unitários
flutter test

# Coverage
flutter test --coverage

# Build modo release
flutter run --release
```

### 7.2 Teste de Performance

```bash
# Profile mode (measurements)
flutter run --profile

# Trace
flutter run --trace-startup
```

### 7.3 Teste em Device Real

```bash
# Listar devices
flutter devices

# Debug em device
flutter run -d <device_id>

# Release em device
flutter run --release -d <device_id>
```

---

## 📊 8. Monitoramento Pós-Deploy

### 8.1 Firebase Console

```
https://console.firebase.google.com
```

Monitor:
- [ ] Crash reports (Crashlytics)
- [ ] Performance metrics
- [ ] User analytics
- [ ] Real-time database

### 8.2 Play Store Console

```
https://play.google.com/console
```

Monitor:
- [ ] Downloads
- [ ] Rating/Reviews
- [ ] Crashes
- [ ] ANRs (Application Not Responding)
- [ ] User feedback

### 8.3 App Store Connect

```
https://appstoreconnect.apple.com
```

Monitor:
- [ ] Sales
- [ ] Rating/Reviews
- [ ] Crashes
- [ ] Performance data

---

## 🔄 9. Atualizações

### 9.1 Versionamento

Usar Semantic Versioning: MAJOR.MINOR.PATCH

```bash
# Atualizar version em pubspec.yaml
# 1.0.0 → 1.0.1 (patch/bugfix)
# 1.0.0 → 1.1.0 (minor/feature)
# 1.0.0 → 2.0.0 (major/breaking)
```

### 9.2 Release Notes

Criar arquivo `RELEASE_NOTES_v1.0.1.md`:

```markdown
# Versão 1.0.1 - Bugfixes

## O que mudou
- [FIX] Corrigido crash ao enviar fotos
- [FIX] Notificações FCM melhoradas
- [IMPROVEMENT] Compressão de imagens otimizada

## Instruções de atualização
1. Baixar nova versão
2. Logar novamente
3. Reportar problemas
```

---

## ✅ Checklist Final

### Antes do Deploy
- [ ] Testes passando (flutter test)
- [ ] Performance OK (flutter run --profile)
- [ ] Firebase credenciais corretas
- [ ] Mercado Pago credenciais corretas
- [ ] Icons e screenshots prontos
- [ ] Privacy policy atualizada
- [ ] Release notes pronta
- [ ] Build APK/AAB/IPA gerado
- [ ] Assinatura digital OK
- [ ] Versão bumped em pubspec.yaml

### Deploy Day
- [ ] Fazer upload em closed testing primeiro
- [ ] Testar com beta users
- [ ] Monitorar crashes/reviews
- [ ] Estar pronto para rollback

### Pós-Deploy
- [ ] Monitorar analytics
- [ ] Responder reviews
- [ ] Monitorar crash reports
- [ ] Estar pronto para patch

---

## 🆘 Troubleshooting

### APK não instala

```bash
# Verificar assinatura
jarsigner -verify -verbose build/app/outputs/flutter-apk/app-release.apk

# Desinstalar versão anterior
adb uninstall com.escoladamanutencao.app
```

### Firebase não funciona

```bash
# Verificar google-services.json
# Verificar SHA1 do keystore no Firebase Console

keytool -list -v -keystore ~/android_keystore/escola_keystore.jks
```

### Mercado Pago não funciona

```bash
# Verificar chaves em .env
# Testar com SANDBOX mode primeiro
# Verificar logs: flutter logs
```

---

## 📞 Contato

**Suporte:**
- Email: dev@escoladamanutencao.com.br
- Firebase: https://console.firebase.google.com
- Mercado Pago: https://www.mercadopago.com.br/developers

---

**Status**: Pronto para produção ✅

Dernière mise à jour: 2026-09-16
