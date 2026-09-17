import 'package:flutter/material.dart';
import '../services/api_service.dart';

class PaymentMethod {
  final String id;
  final String name;
  final String type; // credit_card, pix, boleto

  PaymentMethod({
    required this.id,
    required this.name,
    required this.type,
  });
}

class Payment {
  final int id;
  final int courseId;
  final int userId;
  final double amount;
  final String status; // pending, completed, failed, refunded
  final String method; // credit_card, pix, boleto
  final String? transactionId;
  final DateTime createdAt;
  final DateTime? completedAt;
  final String? receiptUrl;

  Payment({
    required this.id,
    required this.courseId,
    required this.userId,
    required this.amount,
    required this.status,
    required this.method,
    this.transactionId,
    required this.createdAt,
    this.completedAt,
    this.receiptUrl,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'],
      courseId: json['course_id'],
      userId: json['user_id'],
      amount: double.parse(json['amount'].toString()),
      status: json['status'],
      method: json['method'],
      transactionId: json['transaction_id'],
      createdAt: DateTime.parse(json['created_at']),
      completedAt: json['completed_at'] != null
          ? DateTime.parse(json['completed_at'])
          : null,
      receiptUrl: json['receipt_url'],
    );
  }

  bool get isPending => status == 'pending';
  bool get isCompleted => status == 'completed';
  bool get isFailed => status == 'failed';
  bool get isRefunded => status == 'refunded';
}

class PaymentProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<Payment> _payments = [];
  Payment? _currentPayment;
  List<PaymentMethod> _paymentMethods = [];
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  List<Payment> get payments => _payments;
  Payment? get currentPayment => _currentPayment;
  List<PaymentMethod> get paymentMethods => _paymentMethods;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  /// Carregar histórico de pagamentos
  Future<void> loadPayments() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.getPayments();
      _payments = (response as List<dynamic>)
          .map((p) => Payment.fromJson(p as Map<String, dynamic>))
          .toList();
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Iniciar pagamento (Mercado Pago)
  Future<bool> initiatePayment({
    required int courseId,
    required double amount,
    required String method, // credit_card, pix, boleto
    Map<String, dynamic>? paymentDetails,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.createPayment(
        courseId: courseId,
        amount: amount,
        method: method,
        paymentDetails: paymentDetails,
      );

      _currentPayment = Payment.fromJson(response);
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

  /// Confirmar pagamento (callback do Mercado Pago)
  Future<bool> confirmPayment({
    required int paymentId,
    required String transactionId,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.confirmPayment(
        paymentId: paymentId,
        transactionId: transactionId,
      );

      _currentPayment = Payment.fromJson(response);
      
      // Adicionar ao histórico
      _payments.insert(0, _currentPayment!);
      
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

  /// Processar pagamento PIX
  Future<Map<String, dynamic>?> generatePixQrCode({
    required int paymentId,
    required double amount,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.generatePixQrCode(
        paymentId: paymentId,
        amount: amount,
      );

      _isLoading = false;
      notifyListeners();
      return response;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  /// Gerar boleto
  Future<Map<String, dynamic>?> generateBoleto({
    required int paymentId,
    required double amount,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.generateBoleto(
        paymentId: paymentId,
        amount: amount,
      );

      _isLoading = false;
      notifyListeners();
      return response;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
      notifyListeners();
      return null;
    }
  }

  /// Processar cartão de crédito
  Future<bool> processCardPayment({
    required int paymentId,
    required String cardToken,
    required int installments,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.processCardPayment(
        paymentId: paymentId,
        cardToken: cardToken,
        installments: installments,
      );

      _currentPayment = Payment.fromJson(response);
      _payments.insert(0, _currentPayment!);
      
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

  /// Solicitar reembolso
  Future<bool> refundPayment(int paymentId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.refundPayment(paymentId);
      
      // Atualizar no histórico
      final index =
          _payments.indexWhere((p) => p.id == paymentId);
      if (index != -1) {
        _payments[index] = Payment.fromJson(response);
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

  /// Obter status do pagamento
  Future<void> checkPaymentStatus(int paymentId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response =
          await _apiService.getPaymentStatus(paymentId);
      _currentPayment = Payment.fromJson(response);
      _isLoading = false;
    } catch (e) {
      _errorMessage = e.toString();
      _isLoading = false;
    }
    notifyListeners();
  }

  /// Limpar estado
  void clearCurrent() {
    _currentPayment = null;
    notifyListeners();
  }

  /// Limpar erro
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
