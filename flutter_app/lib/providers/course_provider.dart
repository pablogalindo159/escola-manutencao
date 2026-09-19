import 'package:flutter/material.dart';
import '../models/course_model.dart';
import '../services/api_service.dart';

class CourseProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Course> _courses = [];
  List<Course> _myCourses = [];
  Course? _selectedCourse;
  bool _isLoading = false;
  String? _errorMessage;
  int _currentPage = 1;
  int _totalPages = 1;

  // Getters
  List<Course> get courses => _courses;
  List<Course> get myCourses => _myCourses;
  Course? get selectedCourse => _selectedCourse;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;

  /// Carregar cursos
  Future<void> loadCourses({
    int page = 1,
    int perPage = 20,
    String? category,
    String? level,
    String? search,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getCourses(
        page: page,
        perPage: perPage,
        category: category,
        level: level,
        search: search,
      );

      _courses = (response['data'] as List<dynamic>)
          .map((c) => Course.fromJson(c as Map<String, dynamic>))
          .toList();
      _currentPage = response['pagination']['current_page'];
      _totalPages = response['pagination']['last_page'];
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Carregar meus cursos
  Future<void> loadMyCourses({int page = 1, int perPage = 20}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getMyCourses(
        page: page,
        perPage: perPage,
      );

      _myCourses = (response['data'] as List<dynamic>)
          .map((c) => Course.fromJson(c as Map<String, dynamic>))
          .toList();
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Obter detalhes do curso
  Future<void> selectCourse(int courseId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final courseData = await _apiService.getCourse(courseId);
      _selectedCourse = Course.fromJson(courseData);
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Inscrever-se em um curso
  Future<bool> subscribeToCourse(int courseId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _apiService.subscribeToCourse(courseId);
      
      // Atualizar curso selecionado
      if (_selectedCourse?.id == courseId) {
        _selectedCourse = _selectedCourse?.copyWith(isSubscribed: true);
      }
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// Limpar seleção
  void clearSelection() {
    _selectedCourse = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}

class VideoProgressProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  Map<int, double> _videoProgress = {}; // videoId -> progress %
  Map<int, int> _videoWatchedSeconds = {}; // videoId -> segundos já assistidos
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  Map<int, double> get videoProgress => _videoProgress;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  double getProgress(int videoId) => _videoProgress[videoId] ?? 0.0;
  int getWatchedSeconds(int videoId) => _videoWatchedSeconds[videoId] ?? 0;
  bool isVideoCompleted(int videoId) => (_videoProgress[videoId] ?? 0.0) >= 80.0;
  bool isVideoStarted(int videoId) => (_videoProgress[videoId] ?? 0.0) > 0.0;

  /// Atualizar progresso do vídeo
  Future<void> updateProgress({
    required int videoId,
    required int watchedSeconds,
  }) async {
    try {
      await _apiService.updateVideoProgress(
        videoId: videoId,
        watchedSeconds: watchedSeconds,
      );

      // Atualiza local pra refletir na hora (sem esperar recarregar da API)
      _videoWatchedSeconds[videoId] = watchedSeconds;
      notifyListeners();
    } catch (e) {
      // Silencioso: progresso é "best effort", não deve travar o player
    }
  }

  /// Carregar progresso do curso
  Future<void> loadCourseProgress(int courseId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getCourseProgress(courseId);
      
      _videoProgress.clear();
      _videoWatchedSeconds.clear();
      for (var video in response['videos']) {
        final videoId = video['video_id'] as int;
        _videoProgress[videoId] = (video['progress_percentage'] as num).toDouble();
        _videoWatchedSeconds[videoId] = (video['watched_seconds'] as num?)?.toInt() ?? 0;
      }
      
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Limpar dados
  void clear() {
    _videoProgress.clear();
    _videoWatchedSeconds.clear();
    _errorMessage = null;
    notifyListeners();
  }
}
