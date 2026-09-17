import 'package:flutter/material.dart';
import '../models/repair_model.dart';
import '../services/api_service.dart';

class RepairProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Repair> _repairs = [];
  Repair? _selectedRepair;
  bool _isLoading = false;
  String? _errorMessage;
  int _currentPage = 1;
  int _totalPages = 1;
  String _statusFilter = 'all';

  // Getters
  List<Repair> get repairs => _repairs;
  Repair? get selectedRepair => _selectedRepair;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  int get currentPage => _currentPage;
  int get totalPages => _totalPages;
  String get statusFilter => _statusFilter;

  /// Carregar reparos do usuário
  Future<void> loadRepairs({int page = 1, int perPage = 20, String? status}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getRepairs(
        page: page,
        perPage: perPage,
        status: status,
      );

      _repairs = (response['data'] as List<dynamic>)
          .map((r) => Repair.fromJson(r as Map<String, dynamic>))
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

  /// Obter detalhes do reparo
  Future<void> selectRepair(int repairId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final repairData = await _apiService.getRepair(repairId);
      _selectedRepair = Repair.fromJson(repairData);
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Criar novo reparo
  Future<bool> createRepair({
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
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.createRepair(
        equipmentType: equipmentType,
        customerName: customerName,
        defectDescription: defectDescription,
        diagnosis: diagnosis,
        measurements: measurements,
        componentsReplaced: componentsReplaced,
        solution: solution,
        notes: notes,
        courseId: courseId,
      );

      _selectedRepair = Repair.fromJson(response);
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

  /// Upload de foto do reparo
  Future<bool> uploadRepairPhoto({
    required int repairId,
    required String filePath,
    required String stage,
    String? description,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _apiService.uploadRepairPhoto(
        repairId: repairId,
        filePath: filePath,
        stage: stage,
        description: description,
      );

      // Recarregar reparo para atualizar fotos
      await selectRepair(repairId);
      
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

  /// Enviar reparo para análise
  Future<bool> submitForReview(int repairId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await _apiService.submitRepairForReview(repairId);
      
      // Recarregar reparo
      await selectRepair(repairId);
      
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

  /// Filtrar por status
  void setStatusFilter(String status) {
    _statusFilter = status;
    notifyListeners();
  }

  /// Limpar seleção
  void clearSelection() {
    _selectedRepair = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
