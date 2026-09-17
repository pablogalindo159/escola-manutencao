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
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  Map<int, double> get videoProgress => _videoProgress;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  double getProgress(int videoId) => _videoProgress[videoId] ?? 0.0;
  bool isVideoCompleted(int videoId) => (_videoProgress[videoId] ?? 0.0) >= 80.0;

  /// Atualizar progresso do vídeo
  Future<void> updateProgress({
    required int videoId,
    required int watchedSeconds,
  }) async {
    _isLoading = true;
    _errorMessage = null;

    try {
      await _apiService.updateVideoProgress(
        videoId: videoId,
        watchedSeconds: watchedSeconds,
      );

      // Atualizar progresso local
      // Será recalculado na próxima busca da API
      
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Carregar progresso do curso
  Future<void> loadCourseProgress(int courseId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getCourseProgress(courseId);
      
      _videoProgress.clear();
      for (var video in response['videos']) {
        _videoProgress[video['video_id']] = 
            (video['progress_percentage'] as num).toDouble();
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
    _errorMessage = null;
    notifyListeners();
  }
}
