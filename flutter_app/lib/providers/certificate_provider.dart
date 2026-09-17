import 'package:flutter/material.dart';
import '../models/course_model.dart';
import '../services/api_service.dart';

class Certificate {
  final int id;
  final int courseId;
  final int userId;
  final String courseName;
  final String instructorName;
  final DateTime completedAt;
  final String certificateUrl;
  final String qrCodeUrl;
  final String verificationCode;

  Certificate({
    required this.id,
    required this.courseId,
    required this.userId,
    required this.courseName,
    required this.instructorName,
    required this.completedAt,
    required this.certificateUrl,
    required this.qrCodeUrl,
    required this.verificationCode,
  });

  factory Certificate.fromJson(Map<String, dynamic> json) {
    return Certificate(
      id: json['id'],
      courseId: json['course_id'],
      userId: json['user_id'],
      courseName: json['course_name'],
      instructorName: json['instructor_name'],
      completedAt: DateTime.parse(json['completed_at']),
      certificateUrl: json['certificate_url'],
      qrCodeUrl: json['qr_code_url'],
      verificationCode: json['verification_code'],
    );
  }
}

class CertificateProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Certificate> _certificates = [];
  Certificate? _selectedCertificate;
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  List<Certificate> get certificates => _certificates;
  Certificate? get selectedCertificate => _selectedCertificate;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  /// Carregar certificados do usuário
  Future<void> loadCertificates() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getCertificates();
      _certificates = (response as List<dynamic>)
          .map((c) => Certificate.fromJson(c as Map<String, dynamic>))
          .toList();
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Gerar certificado após conclusão do curso
  Future<bool> generateCertificate(int courseId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response =
          await _apiService.generateCertificate(courseId);
      final certificate = Certificate.fromJson(response);
      _certificates.insert(0, certificate);
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

  /// Obter detalhes do certificado
  Future<void> selectCertificate(int certificateId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final certificateData =
          await _apiService.getCertificate(certificateId);
      _selectedCertificate = Certificate.fromJson(certificateData);
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Download do certificado
  Future<bool> downloadCertificate(Certificate certificate) async {
    try {
      // TODO: Implementar download usando path_provider
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  /// Compartilhar certificado
  Future<bool> shareCertificate(Certificate certificate) async {
    try {
      // TODO: Implementar compartilhamento (Share plugin)
      return true;
    } catch (e) {
      _errorMessage = e.toString();
      notifyListeners();
      return false;
    }
  }

  /// Verificar autenticidade do certificado via código
  Future<bool> verifyCertificate(String verificationCode) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response =
          await _apiService.verifyCertificate(verificationCode);
      _selectedCertificate = Certificate.fromJson(response);
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
    _selectedCertificate = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
