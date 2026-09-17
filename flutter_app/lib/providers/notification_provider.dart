import 'package:flutter/material.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

class NotificationModel {
  final String id;
  final String title;
  final String body;
  final String? imageUrl;
  final Map<String, dynamic>? data;
  final DateTime createdAt;
  bool isRead;

  NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    this.imageUrl,
    this.data,
    required this.createdAt,
    this.isRead = false,
  });

  factory NotificationModel.fromRemoteMessage(RemoteMessage message) {
    return NotificationModel(
      id: message.messageId ?? DateTime.now().toString(),
      title: message.notification?.title ?? 'Notificação',
      body: message.notification?.body ?? '',
      imageUrl: message.notification?.android?.imageUrl,
      data: message.data,
      createdAt: DateTime.now(),
    );
  }
}

class NotificationProvider with ChangeNotifier {
  final FirebaseMessaging _firebaseMessaging = FirebaseMessaging.instance;

  List<NotificationModel> _notifications = [];
  bool _isLoading = false;
  String? _errorMessage;
  String? _deviceToken;
  bool _notificationsEnabled = true;

  // Getters
  List<NotificationModel> get notifications => _notifications;
  List<NotificationModel> get unreadNotifications =>
      _notifications.where((n) => !n.isRead).toList();
  int get unreadCount => unreadNotifications.length;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get deviceToken => _deviceToken;
  bool get notificationsEnabled => _notificationsEnabled;

  /// Inicializar Firebase Messaging
  Future<void> initializeFirebaseMessaging() async {
    _isLoading = true;
    notifyListeners();

    try {
      // Solicitar permissão
      NotificationSettings settings =
          await _firebaseMessaging.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carryForward: true,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );

      _notificationsEnabled =
          settings.authorizationStatus == AuthorizationStatus.authorized;

      // Obter token
      _deviceToken = await _firebaseMessaging.getToken();

      // Listener para mensagens em foreground
      FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

      // Listener para mensagens em background
      FirebaseMessaging.onMessageOpenedApp
          .listen(_handleBackgroundMessage);

      // Configurar handlers adicionais
      FirebaseMessaging.onBackgroundMessage(
        _handleBackgroundMessageStatic,
      );

      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Handler para mensagens em foreground
  void _handleForegroundMessage(RemoteMessage message) {
    final notification = NotificationModel.fromRemoteMessage(message);
    _notifications.insert(0, notification);
    notifyListeners();

    // Mostrar local notification (opcional)
    _showLocalNotification(notification);
  }

  /// Handler para mensagens que abriram o app
  void _handleBackgroundMessage(RemoteMessage message) {
    final notification = NotificationModel.fromRemoteMessage(message);
    _notifications.insert(0, notification);
    notifyListeners();
  }

  /// Handler estático para mensagens em background
  static Future<void> _handleBackgroundMessageStatic(
      RemoteMessage message) async {
    // Processar notificação em background
    print('Handling a background message: ${message.messageId}');
  }

  /// Mostrar notificação local
  void _showLocalNotification(NotificationModel notification) {
    // TODO: Integrar com flutter_local_notifications
    print('Local notification: ${notification.title}');
  }

  /// Marcar notificação como lida
  Future<void> markAsRead(String notificationId) async {
    try {
      final index = _notifications
          .indexWhere((n) => n.id == notificationId);
      if (index != -1) {
        _notifications[index].isRead = true;
        notifyListeners();

        // TODO: Fazer requisição para API marcar como lida
      }
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Marcar todas como lidas
  Future<void> markAllAsRead() async {
    try {
      for (var notification in _notifications) {
        notification.isRead = true;
      }
      notifyListeners();

      // TODO: Fazer requisição para API
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Deletar notificação
  Future<void> deleteNotification(String notificationId) async {
    try {
      _notifications
          .removeWhere((n) => n.id == notificationId);
      notifyListeners();

      // TODO: Fazer requisição para API
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Deletar todas as notificações
  Future<void> deleteAllNotifications() async {
    try {
      _notifications.clear();
      notifyListeners();

      // TODO: Fazer requisição para API
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Habilitar/desabilitar notificações
  Future<void> setNotificationsEnabled(bool enabled) async {
    try {
      _notificationsEnabled = enabled;

      if (enabled) {
        await _firebaseMessaging.getToken();
      } else {
        await _firebaseMessaging.deleteToken();
      }

      notifyListeners();

      // TODO: Fazer requisição para API atualizar preferência
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Assinar tópico
  Future<void> subscribeToTopic(String topic) async {
    try {
      await _firebaseMessaging.subscribeToTopic(topic);
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Desassinar tópico
  Future<void> unsubscribeFromTopic(String topic) async {
    try {
      await _firebaseMessaging.unsubscribeFromTopic(topic);
      notifyListeners();
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
    }
  }

  /// Carregar notificações do histórico
  Future<void> loadNotificationHistory() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      // TODO: Fazer requisição para API GET /notifications
      // Simular dados de exemplo
      _notifications = [];
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
