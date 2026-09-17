import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

enum AuthStatus { initial, authenticated, unauthenticated, loading, error }

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  AuthStatus _status = AuthStatus.initial;
  User? _currentUser;
  String? _errorMessage;

  // Getters
  AuthStatus get status => _status;
  User? get currentUser => _currentUser;
  String? get errorMessage => _errorMessage;
  bool get isAuthenticated => _status == AuthStatus.authenticated;
  bool get isLoading => _status == AuthStatus.loading;

  /// Inicializar - verificar se há token salvo
  Future<void> initialize() async {
    _status = AuthStatus.loading;
    notifyListeners();

    try {
      final token = await _apiService.getToken();
      if (token != null) {
        // Tentar carregar usuário atual
        final userData = await _apiService.getCurrentUser();
        _currentUser = User.fromJson(userData);
        _status = AuthStatus.authenticated;
      } else {
        _status = AuthStatus.unauthenticated;
      }
    } catch (e) {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  /// Registrar
  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
    required String cpf,
  }) async {
    _status = AuthStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        phone: phone,
        cpf: cpf,
      );

      _currentUser = User.fromJson(response['user']);
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  /// Login
  Future<bool> login({
    required String email,
    required String password,
  }) async {
    _status = AuthStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.login(
        email: email,
        password: password,
      );

      _currentUser = User.fromJson(response['user']);
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  /// Atualizar perfil
  Future<bool> updateProfile({
    String? name,
    String? phone,
    String? bio,
    String? avatarUrl,
  }) async {
    _status = AuthStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.updateProfile(
        name: name,
        phone: phone,
        bio: bio,
        avatarUrl: avatarUrl,
      );

      _currentUser = _currentUser?.copyWith(
        name: name ?? _currentUser?.name,
        phone: phone ?? _currentUser?.phone,
        bio: bio ?? _currentUser?.bio,
        avatarUrl: avatarUrl ?? _currentUser?.avatarUrl,
      );
      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  /// Alterar senha
  Future<bool> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    _status = AuthStatus.loading;
    _errorMessage = null;
    notifyListeners();

    try {
      await _apiService.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
        newPasswordConfirmation: newPasswordConfirmation,
      );

      _status = AuthStatus.authenticated;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _status = AuthStatus.error;
      notifyListeners();
      return false;
    }
  }

  /// Logout
  Future<void> logout() async {
    try {
      await _apiService.logout();
    } catch (e) {
      _errorMessage = e.toString();
    }
    
    _currentUser = null;
    _status = AuthStatus.unauthenticated;
    _errorMessage = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
