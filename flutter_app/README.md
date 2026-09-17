# 📱 Escola da Manutenção - App Flutter

**Plataforma de cursos profissionais com sistema de manutenção**

---

## 🎯 Visão Geral

App Flutter completo que consome a API REST Laravel, com:
- ✅ Autenticação JWT
- ✅ 15+ telas
- ✅ Cursos com vídeos
- ✅ Sistema "Meus Reparos" (diferencial)
- ✅ Comunidade (posts/comentários)
- ✅ Certificados com QR Code
- ✅ Notificações push
- ✅ Pagamento com Mercado Pago
- ✅ Visualização offline

---

## 📁 Estrutura do Projeto

```
lib/
├── main.dart                      # Entry point
├── models/
│   ├── user_model.dart           # Usuário
│   ├── course_model.dart         # Cursos e Vídeos
│   ├── repair_model.dart         # Reparos (diferencial!)
│   └── community_model.dart      # Posts, Comentários, Certificados
├── services/
│   ├── api_service.dart          # HTTP client com JWT
│   ├── storage_service.dart      # Banco de dados local
│   ├── notification_service.dart # Push notifications
│   └── payment_service.dart      # Mercado Pago
├── providers/
│   ├── auth_provider.dart        # State: autenticação
│   ├── course_provider.dart      # State: cursos
│   ├── repair_provider.dart      # State: reparos
│   └── community_provider.dart   # State: comunidade
├── screens/
│   ├── auth/
│   │   ├── splash_screen.dart
│   │   ├── login_screen.dart
│   │   └── register_screen.dart
│   ├── home/
│   │   ├── home_screen.dart
│   │   ├── course_list_screen.dart
│   │   └── course_detail_screen.dart
│   ├── video/
│   │   ├── video_player_screen.dart
│   │   └── course_progress_screen.dart
│   ├── repairs/
│   │   ├── repairs_list_screen.dart
│   │   ├── repair_detail_screen.dart
│   │   ├── repair_form_screen.dart
│   │   └── repair_photo_screen.dart
│   ├── community/
│   │   ├── posts_screen.dart
│   │   ├── post_detail_screen.dart
│   │   └── post_form_screen.dart
│   ├── certificates/
│   │   ├── certificates_list_screen.dart
│   │   └── certificate_detail_screen.dart
│   ├── profile/
│   │   ├── profile_screen.dart
│   │   └── edit_profile_screen.dart
│   └── splash/
│       └── splash_screen.dart
├── widgets/
│   ├── course_card.dart
│   ├── video_card.dart
│   ├── repair_card.dart
│   ├── post_card.dart
│   ├── bottom_nav_bar.dart
│   ├── loading_shimmer.dart
│   └── error_widget.dart
├── utils/
│   ├── constants.dart
│   ├── app_colors.dart
│   ├── app_styles.dart
│   └── validators.dart
└── config/
    ├── routes.dart
    ├── theme.dart
    └── dependencies.dart
```

---

## 🚀 Instalação & Setup

### Pré-requisitos
- Flutter 3.0+
- Dart 3.0+
- API Laravel rodando em `http://localhost:8000`

### Passos

```bash
# 1. Clonar repositório
git clone <repo>
cd flutter_app

# 2. Instalar dependências
flutter pub get

# 3. Configurar .env
cp .env.example .env
# Editar com seus dados

# 4. Gerar código (Hive, build_runner)
flutter pub run build_runner build

# 5. Rodar app
flutter run
```

---

## 📦 Dependências Principais

| Pacote | Versão | Uso |
|--------|--------|-----|
| **provider** | 6.0.0 | State Management |
| **dio** | 5.3.0 | HTTP requests |
| **hive** | 2.2.0 | Banco de dados local |
| **sqflite** | 2.3.0 | SQLite (cache) |
| **firebase_messaging** | 14.6.0 | Push notifications |
| **mercado_pago_mobile** | 2.0.0 | Pagamentos |
| **video_player** | 2.7.0 | Reprodução de vídeos |
| **image_picker** | 1.0.0 | Câmera / galeria |
| **qr_flutter** | 4.1.0 | QR codes |

---

## 🎨 Design System

### Cores Principais
```dart
primary: #0066FF       // Azul
secondary: #FF6B6B     // Vermelho
success: #51CF66       // Verde
warning: #FFC107       // Amarelo
error: #FF6B6B         // Vermelho
```

### Tipografia
- **Título**: Poppins Bold 24px
- **Subtítulo**: Poppins SemiBold 16px
- **Corpo**: Inter Regular 14px
- **Label**: Inter Regular 12px

---

## 🔐 Autenticação

### Fluxo
```
1. Register/Login → recebe JWT
2. Salva token em FlutterSecureStorage
3. Adiciona token em todo request (Authorization: Bearer {token})
4. Interceptor refresh token se expirado
5. Logout → limpa token
```

### Protegido
- Todas as rotas (exceto auth) requerem JWT
- Token refresh automático
- Logout ao expirar

---

## 📱 Telas Principais (15+)

### 🔐 Autenticação (3)
- [ ] Splash Screen
- [ ] Login Screen
- [ ] Register Screen

### 📚 Cursos (4)
- [ ] Home / Course List
- [ ] Course Detail
- [ ] Video Player
- [ ] Course Progress

### 🔧 Reparos (4) ⭐
- [ ] Repairs List
- [ ] Repair Detail
- [ ] Repair Form
- [ ] Repair Photos

### 💬 Comunidade (3)
- [ ] Posts List
- [ ] Post Detail
- [ ] Post Form

### 🏆 Certificados (2)
- [ ] Certificates List
- [ ] Certificate Detail

### 👤 Perfil (2)
- [ ] Profile
- [ ] Edit Profile

---

## 🔄 State Management (Provider)

### AuthProvider
```dart
- status: AuthStatus (initial, loading, authenticated, error)
- currentUser: User?
- methods: initialize(), register(), login(), logout()
```

### CourseProvider
```dart
- courses: List<Course>
- myCourses: List<Course>
- selectedCourse: Course?
- methods: loadCourses(), loadMyCourses(), selectCourse()
```

### RepairProvider
```dart
- repairs: List<Repair>
- methods: loadRepairs(), createRepair(), uploadPhoto()
```

### CommunityProvider
```dart
- posts: List<Post>
- comments: Map<int, List<Comment>>
- methods: loadPosts(), createPost(), addComment()
```

---

## 🌐 Integração com API

Todos os requests passam por `ApiService`:

```dart
// Exemplo
final response = await apiService.getCourses(
  page: 1,
  category: 'celulares',
  search: 'tela',
);

// Erros são tratados e retornam mensagens amigáveis
```

---

## 🔔 Notificações Push (Firebase)

```dart
// Inicializar
await NotificationService.init();

// Receber notification
FirebaseMessaging.onMessage.listen((message) {
  showNotification(message);
});
```

---

## 💳 Pagamentos (Mercado Pago)

```dart
// Criar preferência
final preferenceId = await paymentService.createPreference(
  courseId: 1,
  amount: 99.90,
);

// Abrir checkout
await paymentService.openCheckout(preferenceId);
```

---

## 🗄️ Banco de Dados Local

### Hive (Cache)
```dart
// Armazenar usuário
await userBox.put('current_user', user);

// Recuperar
final user = userBox.get('current_user');
```

### SQLite (Histórico)
```dart
// Salvar reparos offline
await repairDb.insert('repairs', repairData);
```

---

## 📹 Reprodução de Vídeos

### Video Player
```dart
final controller = VideoPlayerController.network(videoUrl);
await controller.initialize();
// Usar com Chewie para UI completa
```

### Progresso
```dart
// Atualizar progresso a cada 10 segundos
final position = controller.value.position.inSeconds;
await videoProvider.updateProgress(
  videoId: videoId,
  watchedSeconds: position,
);
```

---

## 📸 Câmera & Galeria

### Para Reparos
```dart
final picker = ImagePicker();
final image = await picker.pickImage(source: ImageSource.camera);

// Upload
await apiService.uploadRepairPhoto(
  repairId: repairId,
  filePath: image.path,
  stage: 'before',
);
```

---

## 🎯 QR Code - Certificados

```dart
// Gerar QR Code
QrImage(
  data: certificate.qrCodeData,
  size: 300,
  backgroundColor: Colors.white,
)

// Escanear para verificar
final result = await barcodeScan.scan();
```

---

## 🧪 Testes

```bash
# Testes unitários
flutter test

# Cobertura
flutter test --coverage
```

---

## 📊 Performance

- **Lazy Loading**: Cursos com paginação
- **Caching**: Posts/comentários em memória
- **Offline**: Reparos salvos localmente até envio
- **Imagens**: Cached com `cached_network_image`
- **Vídeos**: Streaming com qualidade adaptativa

---

## 🚀 Build & Deploy

### APK
```bash
flutter build apk --release
# Saída: build/app/outputs/apk/release/app-release.apk
```

### iOS
```bash
flutter build ios --release
# Saída: build/ios/iphoneos/Runner.app
```

### Web (futuro)
```bash
flutter build web --release
# Saída: build/web/
```

---

## 📝 Variáveis de Ambiente

Criar `.env`:
```
API_URL=http://localhost:8000
FIREBASE_PROJECT_ID=seu-projeto
MERCADO_PAGO_PUBLIC_KEY=pk_xxx
MERCADO_PAGO_ACCESS_TOKEN=acc_xxx
```

---

## 🐛 Troubleshooting

### Erro de Conexão
```
Verificar se API está rodando: http://localhost:8000/api/health
```

### Token Expirado
```
App refresha automaticamente, ou faz logout se inválido
```

### Imagens não carregam
```
Verificar permissões em AndroidManifest.xml e Info.plist
```

---

## 📚 Documentação Adicional

- [Flutter Docs](https://flutter.dev/docs)
- [Dio HTTP](https://pub.dev/packages/dio)
- [Provider](https://pub.dev/packages/provider)
- [Firebase](https://firebase.google.com/docs/flutter/setup)

---

## 👨‍💻 Autor

Criado por Claude para Escola da Manutenção

---

## 📄 Licença

Proprietary © 2024 Escola da Manutenção

---

**Status**: ⏳ Em Desenvolvimento FASE 3  
**Última atualização**: 2026-09-16
