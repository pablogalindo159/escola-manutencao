import 'package:flutter/material.dart';
import '../models/community_model.dart';
import '../services/api_service.dart';

class CommunityProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Post> _posts = [];
  Post? _selectedPost;
  bool _isLoading = false;
  String? _errorMessage;
  int _currentPage = 1;
  int _totalPages = 1;
  int? _selectedCourseId;
  int? _filterCourseId;
  // Cursos do aluno (id, title) - usados no filtro e no formulário de post
  List<Map<String, dynamic>> _courses = [];

  // Getters
  List<Post> get posts => _posts;
  Post? get selectedPost => _selectedPost;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;
  int? get filterCourseId => _filterCourseId;
  List<Map<String, dynamic>> get courses => _courses;

  /// Carregar posts do curso
  Future<void> loadPosts({
    int? courseId,
    int? filterCourseId,
    int page = 1,
    int perPage = 20,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    _selectedCourseId = courseId;
    _filterCourseId = filterCourseId;
    notifyListeners();

    try {
      final response = await _apiService.getPosts(
        courseId: courseId,
        filterCourseId: filterCourseId,
        page: page,
        perPage: perPage,
      );

      final courses = response['courses'];
      if (courses is List) {
        _courses = courses
            .map((c) => Map<String, dynamic>.from(c as Map))
            .toList();
      }

      _posts = (response['data'] as List<dynamic>)
          .map((p) => Post.fromJson(p as Map<String, dynamic>))
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

  /// Obter detalhes do post
  Future<void> selectPost(int postId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final postData = await _apiService.getPost(postId);
      _selectedPost = Post.fromJson(postData);
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Criar novo post
  Future<bool> createPost({
    required String title,
    required String content,
    int? courseId,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.createPost(
        title: title,
        content: content,
        courseId: courseId,
      );

      final newPost = Post.fromJson(response);
      _posts.insert(0, newPost);
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

  /// Like em um post
  Future<bool> likePost(int postId) async {
    try {
      await _apiService.likePost(postId);
      
      // Atualizar localmente
      final postIndex = _posts.indexWhere((p) => p.id == postId);
      if (postIndex != -1) {
        _posts[postIndex] = _posts[postIndex].copyWith(
          likesCount: _posts[postIndex].likesCount + 1,
        );
        notifyListeners();
      }
      
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  /// Unlike em um post
  Future<bool> unlikePost(int postId) async {
    try {
      await _apiService.unlikePost(postId);
      
      // Atualizar localmente
      final postIndex = _posts.indexWhere((p) => p.id == postId);
      if (postIndex != -1) {
        _posts[postIndex] = _posts[postIndex].copyWith(
          likesCount: _posts[postIndex].likesCount - 1,
        );
        notifyListeners();
      }
      
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  /// Adicionar comentário
  Future<bool> addComment({
    required int postId,
    required String content,
    int? parentCommentId,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _apiService.createComment(
        postId: postId,
        content: content,
        parentCommentId: parentCommentId,
      );
      
      // Recarregar post
      await selectPost(postId);
      
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
    _selectedPost = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
