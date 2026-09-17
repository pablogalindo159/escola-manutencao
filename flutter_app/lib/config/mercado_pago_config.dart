import 'package:mercado_pago_mobile_checkout/mercado_pago_mobile_checkout.dart';

/// Configuração do Mercado Pago
/// 
/// Antes de usar, coloque suas credenciais no .env ou através de variáveis de ambiente
class MercadoPagoConfig {
  /// Chave pública do Mercado Pago
  /// Obtém do: https://www.mercadopago.com.br/developers/panel/credentials
  static const String publicKey = 'TODO_REPLACE_WITH_PUBLIC_KEY';

  /// Chave privada do Mercado Pago (apenas no backend!)
  /// NUNCA exponha isso no app, use apenas para testes locais
  static const String accessToken = 'TODO_REPLACE_WITH_ACCESS_TOKEN';

  /// Ambiente: SANDBOX para testes, PRODUCTION para produção
  static const String environment = 'SANDBOX'; // Mudar para PRODUCTION

  /// Initializar Mercado Pago
  static Future<void> initialize() async {
    try {
      // Não é necessário inicializar explicitamente no mobile
      // O SDK é ativado automaticamente quando usado
      print('Mercado Pago configured for: $environment');
    } catch (e) {
      print('Error configuring Mercado Pago: $e');
    }
  }

  /// Gerar token para cartão (client-side)
  /// 
  /// Dados necessários:
  /// - cardNumber: número do cartão (16 dígitos)
  /// - expirationMonth: mês de expiração (1-12)
  /// - expirationYear: ano de expiração (2 últimos dígitos)
  /// - securityCode: código de segurança (3-4 dígitos)
  /// - cardholderName: nome do titular
  static Future<String?> tokenizeCard({
    required String cardNumber,
    required int expirationMonth,
    required int expirationYear,
    required String securityCode,
    required String cardholderName,
  }) async {
    try {
      // TODO: Implementar tokenização usando MercadoPagoSDK
      // Exemplo:
      // var cardToken = await MercadoPagoMobileCheckout.tokenizeCard(
      //   publicKey: publicKey,
      //   cardNumber: cardNumber,
      //   expirationMonth: expirationMonth,
      //   expirationYear: expirationYear,
      //   securityCode: securityCode,
      //   cardholderName: cardholderName,
      // );
      // return cardToken;
      return null;
    } catch (e) {
      print('Error tokenizing card: $e');
      return null;
    }
  }

  /// Iniciar checkout do Mercado Pago
  /// 
  /// Retorna o status do pagamento:
  /// - APPROVED: Pagamento aprovado
  /// - PENDING: Pagamento pendente
  /// - REJECTED: Pagamento rejeitado
  static Future<String?> startCheckout({
    required double amount,
    required String title,
    required String description,
    required Map<String, String> metadata,
  }) async {
    try {
      // TODO: Implementar checkout usando MercadoPagoMobileCheckout
      // Exemplo:
      // final result = await MercadoPagoMobileCheckout.startCheckout(
      //   publicKey: publicKey,
      //   preferenceId: preferenceId, // Criado no backend
      //   metadata: metadata,
      // );
      // return result;
      return null;
    } catch (e) {
      print('Error starting checkout: $e');
      return null;
    }
  }

  /// Gerar QR Code para PIX
  /// 
  /// Retorna:
  /// - qrCode: string do QR Code
  /// - copyAndPaste: código para copiar e colar
  static Future<Map<String, String>?> generatePixQrCode({
    required double amount,
    required String description,
  }) async {
    try {
      // TODO: Chamar API do backend que gera PIX
      // const pixUrl = '/api/payments/pix/generate';
      // final response = await apiService.post(
      //   pixUrl,
      //   {'amount': amount, 'description': description},
      // );
      // return {'qrCode': response['qr_code'], 'copyAndPaste': response['copy_paste']};
      return null;
    } catch (e) {
      print('Error generating PIX QR Code: $e');
      return null;
    }
  }

  /// Gerar boleto bancário
  /// 
  /// Retorna:
  /// - boletoNumber: número do boleto
  /// - boletoUrl: link para visualizar/baixar
  /// - expirationDate: data de vencimento
  static Future<Map<String, dynamic>?> generateBoleto({
    required double amount,
    required String description,
    required String payerName,
    required String payerEmail,
  }) async {
    try {
      // TODO: Chamar API do backend que gera boleto
      // const boletoUrl = '/api/payments/boleto/generate';
      // final response = await apiService.post(
      //   boletoUrl,
      //   {
      //     'amount': amount,
      //     'description': description,
      //     'payer_name': payerName,
      //     'payer_email': payerEmail,
      //   },
      // );
      // return response;
      return null;
    } catch (e) {
      print('Error generating boleto: $e');
      return null;
    }
  }

  /// Webhook de confirmação de pagamento
  /// 
  /// Registrar no Mercado Pago:
  /// https://www.mercadopago.com.br/developers/panel/webhooks
  /// 
  /// URL: https://escoladamanutencao.com.br/api/webhooks/mercado-pago
  /// Eventos:
  /// - payment.created
  /// - payment.updated
  /// - payment.rejected
  static const String webhookUrl =
      'https://escoladamanutencao.com.br/api/webhooks/mercado-pago';

  /// Estatutos de pagamento suportados
  static const List<String> paymentStatuses = [
    'approved',    // Aprovado
    'pending',     // Pendente
    'authorized',  // Autorizado
    'in_process',  // Em processamento
    'rejected',    // Rejeitado
    'cancelled',   // Cancelado
    'refunded',    // Reembolsado
    'charged_back' // Contestado
  ];

  /// Métodos de pagamento suportados
  static const Map<String, String> paymentMethods = {
    'credit_card': 'Cartão de Crédito',
    'debit_card': 'Cartão de Débito',
    'pix': 'PIX',
    'boleto': 'Boleto Bancário',
    'bank_transfer': 'Transferência Bancária',
  };

  /// Instituições bancárias suportadas (para boleto)
  static const List<String> bankInstitutions = [
    '001', // Banco do Brasil
    '033', // Banco Santander
    '104', // Caixa Econômica Federal
    '237', // Banco Bradesco
    '341', // Itaú Unibanco
    '389', // Banco Mercantil do Brasil
  ];

  /// Validar CPF do pagador (Mercado Pago valida automaticamente)
  static bool validateCPF(String cpf) {
    cpf = cpf.replaceAll(RegExp(r'\D'), '');
    if (cpf.length != 11) return false;

    if (cpf.split('').every((c) => c == cpf[0])) return false;

    int sum = 0;
    int remainder;

    for (int i = 1; i <= 9; i++) {
      sum += int.parse(cpf[i - 1]) * (11 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder == 10 || remainder == 11) remainder = 0;
    if (remainder != int.parse(cpf[9])) return false;

    sum = 0;
    for (int i = 1; i <= 10; i++) {
      sum += int.parse(cpf[i - 1]) * (12 - i);
    }

    remainder = (sum * 10) % 11;
    if (remainder == 10 || remainder == 11) remainder = 0;
    if (remainder != int.parse(cpf[10])) return false;

    return true;
  }
}
