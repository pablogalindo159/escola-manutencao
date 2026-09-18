import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:logger/logger.dart';

class ApiService {
  // TODO: trocar para https://escoladamanutencao.com.br/api quando o domínio
  // e o SSL estiverem configurados. Por enquanto aponta pro IP da VPS.
  static const String baseUrl = 'http://198.199.64.162/api';
  
  late Dio _dio;
  final _storage = const FlutterSecureStorage();
  final _logger = Logger();

  ApiService() {
    _dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 10),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    // Interceptor para adicionar token
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await getToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          _logger.d('REQUEST: ${options.method} ${options.path}');
          return handler.next(options);
        },
        onResponse: (response, handler) {
          _logger.d('RESPONSE: ${response.statusCode} ${response.requestOptions.path}');
          return handler.next(response);
        },
        onError: (error, handler) {
          _logger.e('ERROR: ${error.message}');
          return handler.next(error);
        },
      ),
    );
  }

  // ==================== AUTENTICAÇÃO ====================

  /// Registrar novo usuário
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
    required String cpf,
  }) async {
    try {
      final response = await _dio.post(
        '/auth/register',
        data: {
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'phone': phone,
          'cpf': cpf,
        },
      );
      
      if (response.statusCode == 201) {
        await _saveToken(response.data['access_token']);
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Fazer login
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post(
        '/auth/login',
        data: {
          'email': email,
          'password': password,
        },
      );
      
      if (response.statusCode == 200) {
        await _saveToken(response.data['access_token']);
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obter dados do usuário autenticado
  Future<Map<String, dynamic>> getCurrentUser() async {
    try {
      final response = await _dio.get('/auth/me');
      if (response.statusCode == 200) {
        return response.data['user'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Atualizar perfil
  Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? phone,
    String? bio,
    String? avatarUrl,
  }) async {
    try {
      final response = await _dio.put(
        '/auth/profile',
        data: {
          if (name != null) 'name': name,
          if (phone != null) 'phone': phone,
          if (bio != null) 'bio': bio,
          if (avatarUrl != null) 'avatar_url': avatarUrl,
        },
      );
      
      if (response.statusCode == 200) {
        return response.data['user'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Alterar senha
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newPasswordConfirmation,
  }) async {
    try {
      final response = await _dio.put(
        '/auth/change-password',
        data: {
          'current_password': currentPassword,
          'new_password': newPassword,
          'new_password_confirmation': newPasswordConfirmation,
        },
      );
      
      if (response.statusCode != 200) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Logout
  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
      await _clearToken();
    } on DioException catch (e) {
      await _clearToken();
      throw _handleError(e);
    }
  }

  // ==================== CURSOS ====================

  /// Listar cursos
  Future<Map<String, dynamic>> getCourses({
    int page = 1,
    int perPage = 20,
    String? category,
    String? level,
    String? search,
  }) async {
    try {
      final response = await _dio.get(
        '/courses',
        queryParameters: {
          'page': page,
          'per_page': perPage,
          if (category != null) 'category': category,
          if (level != null) 'level': level,
          if (search != null) 'search': search,
        },
      );
      
      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obter detalhes do curso
  Future<Map<String, dynamic>> getCourse(int courseId) async {
    try {
      final response = await _dio.get('/courses/$courseId');
      
      if (response.statusCode == 200) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Listar cursos do usuário
  Future<Map<String, dynamic>> getMyCourses({int page = 1, int perPage = 20}) async {
    try {
      final response = await _dio.get(
        '/courses/my-courses',
        queryParameters: {
          'page': page,
          'per_page': perPage,
        },
      );
      
      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Inscrever-se em um curso
  Future<void> subscribeToCourse(int courseId) async {
    try {
      final response = await _dio.post('/courses/$courseId/subscribe');
      
      if (response.statusCode != 201) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== VÍDEOS ====================

  /// Obter detalhes do vídeo
  Future<Map<String, dynamic>> getVideo(int videoId) async {
    try {
      final response = await _dio.get('/videos/$videoId');
      
      if (response.statusCode == 200) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Atualizar progresso do vídeo
  Future<void> updateVideoProgress({
    required int videoId,
    required int watchedSeconds,
  }) async {
    try {
      final response = await _dio.post(
        '/videos/$videoId/progress',
        data: {
          'watched_seconds': watchedSeconds,
        },
      );
      
      if (response.statusCode != 200) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obter progresso do curso
  Future<Map<String, dynamic>> getCourseProgress(int courseId) async {
    try {
      final response = await _dio.get('/progress/courses/$courseId');
      
      if (response.statusCode == 200) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== REPAROS ====================

  /// Listar reparos do usuário
  Future<Map<String, dynamic>> getRepairs({int page = 1, int perPage = 20, String? status}) async {
    try {
      final response = await _dio.get(
        '/repairs',
        queryParameters: {
          'page': page,
          'per_page': perPage,
          if (status != null) 'status': status,
        },
      );
      
      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obter detalhes do reparo
  Future<Map<String, dynamic>> getRepair(int repairId) async {
    try {
      final response = await _dio.get('/repairs/$repairId');
      
      if (response.statusCode == 200) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Criar novo reparo
  Future<Map<String, dynamic>> createRepair({
    required String equipmentType,
    String? customerName,
    required String defectDescription,
    String? diagnosis,
    Map<String, dynamic>? measurements,
    Map<String, dynamic>? componentsReplaced,
    String? solution,
    String? notes,
    int? courseId,
  }) async {
    try {
      final response = await _dio.post(
        '/repairs',
        data: {
          'equipment_type': equipmentType,
          'customer_name': customerName,
          'defect_description': defectDescription,
          'diagnosis': diagnosis,
          'measurements': measurements,
          'components_replaced': componentsReplaced,
          'solution': solution,
          'notes': notes,
          'course_id': courseId,
        },
      );
      
      if (response.statusCode == 201) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Upload de foto do reparo
  Future<Map<String, dynamic>> uploadRepairPhoto({
    required int repairId,
    required String filePath,
    required String stage,
    String? description,
  }) async {
    try {
      final formData = FormData.fromMap({
        'photo': await MultipartFile.fromFile(filePath),
        'stage': stage,
        'description': description,
      });

      final response = await _dio.post(
        '/repairs/$repairId/photos',
        data: formData,
      );
      
      if (response.statusCode == 201) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Enviar reparo para análise
  Future<void> submitRepairForReview(int repairId) async {
    try {
      final response = await _dio.post('/repairs/$repairId/submit');
      
      if (response.statusCode != 200) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== CERTIFICADOS ====================

  /// Listar certificados do usuário
  Future<Map<String, dynamic>> getCertificates({int page = 1, int perPage = 20}) async {
    try {
      final response = await _dio.get(
        '/certificates',
        queryParameters: {
          'page': page,
          'per_page': perPage,
        },
      );
      
      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Gerar certificado
  Future<Map<String, dynamic>> generateCertificate(int courseId) async {
    try {
      final response = await _dio.post(
        '/certificates',
        data: {
          'course_id': courseId,
        },
      );
      
      if (response.statusCode == 201) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== COMUNIDADE (POSTS) ====================

  /// Listar posts de um curso
  Future<Map<String, dynamic>> getPosts({
    int? courseId,
    int page = 1,
    int perPage = 20,
  }) async {
    if (courseId == null) {
      throw Exception('É necessário informar o curso para listar os posts');
    }
    try {
      final response = await _dio.get(
        '/posts/course/$courseId',
        queryParameters: {
          'page': page,
          'per_page': perPage,
        },
      );

      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Obter detalhes de um post
  Future<Map<String, dynamic>> getPost(int postId) async {
    try {
      final response = await _dio.get('/posts/$postId');

      if (response.statusCode == 200) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Criar novo post
  Future<Map<String, dynamic>> createPost({
    required String title,
    required String content,
    int? courseId,
  }) async {
    if (courseId == null) {
      throw Exception('É necessário informar o curso para criar um post');
    }
    try {
      final response = await _dio.post(
        '/posts',
        data: {
          'course_id': courseId,
          'title': title,
          'content': content,
        },
      );

      if (response.statusCode == 201) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Curtir post
  Future<void> likePost(int postId) async {
    try {
      final response = await _dio.post('/posts/$postId/like');

      if (response.statusCode != 200 && response.statusCode != 201) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Descurtir post
  Future<void> unlikePost(int postId) async {
    try {
      final response = await _dio.delete('/posts/$postId/like');

      if (response.statusCode != 200) {
        throw Exception(response.data['message']);
      }
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Criar comentário
  Future<Map<String, dynamic>> createComment({
    required int postId,
    required String content,
    int? parentCommentId,
  }) async {
    try {
      final response = await _dio.post(
        '/comments',
        data: {
          'post_id': postId,
          'content': content,
          if (parentCommentId != null) 'parent_comment_id': parentCommentId,
        },
      );

      if (response.statusCode == 201) {
        return response.data['data'];
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== TRANSMISSÕES AO VIVO ====================

  /// Transmissões ao vivo agora
  Future<List<dynamic>> getLiveStreamsNow() async {
    try {
      final response = await _dio.get('/live-streams/live-now');
      if (response.statusCode == 200) {
        return response.data['data'] as List<dynamic>;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Próximas transmissões agendadas
  Future<List<dynamic>> getUpcomingLiveStreams() async {
    try {
      final response = await _dio.get('/live-streams/upcoming');
      if (response.statusCode == 200) {
        return response.data['data'] as List<dynamic>;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  /// Detalhes de uma transmissão
  Future<Map<String, dynamic>> getLiveStream(int id) async {
    try {
      final response = await _dio.get('/live-streams/$id');
      if (response.statusCode == 200) {
        return response.data;
      }
      throw Exception(response.data['message']);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // ==================== UTILITÁRIOS ====================

  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }

  Future<void> _saveToken(String token) async {
    await _storage.write(key: 'auth_token', value: token);
  }

  Future<void> _clearToken() async {
    await _storage.delete(key: 'auth_token');
  }

  String _handleError(DioException error) {
    if (error.response != null) {
      return error.response?.data['message'] ?? 'Erro desconhecido';
    } else if (error.type == DioExceptionType.connectionTimeout) {
      return 'Tempo de conexão esgotado';
    } else if (error.type == DioExceptionType.receiveTimeout) {
      return 'Tempo de resposta esgotado';
    }
    return error.message ?? 'Erro desconhecido';
  }
}
