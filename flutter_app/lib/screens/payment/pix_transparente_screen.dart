import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:intl/intl.dart';
import 'package:escola_manutencao/services/api_service.dart';
import 'package:escola_manutencao/models/course_model.dart';

class PixTransparenteScreen extends StatefulWidget {
  final Course course;
  final double amount;

  const PixTransparenteScreen({
    Key? key,
    required this.course,
    required this.amount,
  }) : super(key: key);

  @override
  State<PixTransparenteScreen> createState() => _PixTransparenteScreenState();
}

class _PixTransparenteScreenState extends State<PixTransparenteScreen> {
  late ApiService apiService;
  late Timer pollingTimer;
  
  String? paymentId;
  String? qrCodeImage;
  String? pixCopyPaste;
  DateTime? expiresAt;
  
  bool isLoading = true;
  bool isPolling = false;
  bool paymentConfirmed = false;
  String? errorMessage;
  
  int remainingSeconds = 0;

  @override
  void initState() {
    super.initState();
    apiService = ApiService();
    gerarPixTransparente();
  }

  @override
  void dispose() {
    pollingTimer.cancel();
    super.dispose();
  }

  /// Gerar PIX direto no Mercado Pago
  Future<void> gerarPixTransparente() async {
    try {
      setState(() {
        isLoading = true;
        errorMessage = null;
      });

      final response = await apiService.post(
        '/payments/pix/gerar',
        data: {
          'course_id': widget.course.id,
          'amount': widget.amount,
          'description': 'Acesso ao curso: ${widget.course.title}',
        },
      );

      if (response.statusCode == 200) {
        final data = response.data['data'] ?? response.data;
        
        setState(() {
          paymentId = data['payment_id'].toString();
          qrCodeImage = data['qr_code'];
          pixCopyPaste = data['pix_copy_paste'];
          expiresAt = DateTime.parse(data['expires_at']);
          isLoading = false;
        });

        // Iniciar polling de status
        iniciarPolling();
        
        // Iniciar timer de contagem regressiva
        iniciarTimer();
      } else {
        setState(() {
          errorMessage = response.data['message'] ?? 'Erro ao gerar PIX';
          isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        errorMessage = 'Erro: $e';
        isLoading = false;
      });
    }
  }

  /// Iniciar polling do status do pagamento
  void iniciarPolling() {
    pollingTimer = Timer.periodic(Duration(seconds: 3), (_) async {
      if (!mounted || paymentConfirmed) return;

      try {
        final response = await apiService.get(
          '/payments/pix/$paymentId/status',
        );

        if (response.statusCode == 200) {
          final status = response.data['status'];

          if (status == 'completed') {
            setState(() {
              paymentConfirmed = true;
              isPolling = false;
            });
            pollingTimer.cancel();

            // Mostrar sucesso e navegar
            _mostrarSucesso();
          }
        }
      } catch (e) {
        // Continua tentando
      }
    });

    setState(() {
      isPolling = true;
    });
  }

  /// Timer de contagem regressiva (1 hora para pagar)
  void iniciarTimer() {
    Timer.periodic(Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }

      final agora = DateTime.now();
      final diferenca = expiresAt!.difference(agora).inSeconds;

      if (diferenca <= 0) {
        timer.cancel();
        setState(() {
          errorMessage = 'PIX expirou. Gere um novo.';
        });
      } else {
        setState(() {
          remainingSeconds = diferenca;
        });
      }
    });
  }

  /// Copiar código PIX para clipboard
  void copiarCodigoPix() {
    if (pixCopyPaste != null) {
      Clipboard.setData(ClipboardData(text: pixCopyPaste!));
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Código PIX copiado!'),
          duration: Duration(seconds: 2),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  /// Mostrar tela de sucesso
  void _mostrarSucesso() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green, size: 28),
            SizedBox(width: 12),
            Text('Pagamento Confirmado!'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Seu PIX foi recebido com sucesso.'),
            SizedBox(height: 12),
            Text(
              'Você agora tem acesso ao curso:',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            Text(
              widget.course.title,
              style: TextStyle(
                color: Colors.blue,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop(); // Fecha dialog
              Navigator.of(context).pop(); // Fecha PixTransparenteScreen
              Navigator.of(context).pushNamed('/meus-cursos');
            },
            child: Text('Ir para Meus Cursos'),
          ),
        ],
      ),
    );
  }

  /// Formatar segundos em MM:SS
  String formatarTempo(int segundos) {
    int minutos = segundos ~/ 60;
    int secs = segundos % 60;
    return '${minutos.toString().padLeft(2, '0')}:${secs.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Pagamento PIX'),
        elevation: 0,
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : errorMessage != null
              ? _buildErroWidget()
              : _buildPixWidget(),
    );
  }

  /// Widget de erro
  Widget _buildErroWidget() {
    return Center(
      child: Padding(
        padding: EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red),
            SizedBox(height: 16),
            Text(
              'Erro ao Gerar PIX',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 12),
            Text(
              errorMessage ?? 'Tente novamente',
              style: TextStyle(color: Colors.grey[700]),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: gerarPixTransparente,
              icon: Icon(Icons.refresh),
              label: Text('Tentar Novamente'),
            ),
            SizedBox(height: 12),
            OutlinedButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Voltar'),
            ),
          ],
        ),
      ),
    );
  }

  /// Widget principal do PIX
  Widget _buildPixWidget() {
    return SingleChildScrollView(
      child: Padding(
        padding: EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header do curso
            Card(
              elevation: 2,
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Acesso ao Curso',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      widget.course.title,
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Valor:',
                          style: TextStyle(color: Colors.grey[600]),
                        ),
                        Text(
                          'R\$ ${widget.amount.toStringAsFixed(2)}',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.green[700],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),

            SizedBox(height: 24),

            // QR Code
            if (qrCodeImage != null)
              Card(
                elevation: 2,
                child: Padding(
                  padding: EdgeInsets.all(20),
                  child: Column(
                    children: [
                      Text(
                        'Escaneie o QR Code com seu banco',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey[700],
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 16),
                      QrImage(
                        data: qrCodeImage!,
                        version: QrVersions.auto,
                        size: 250,
                        gapless: true,
                        errorStateBuilder: (context, err) {
                          return Container(
                            color: Colors.grey[200],
                            child: Center(child: Text('Erro ao carregar QR')),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              )
            else
              SizedBox.shrink(),

            SizedBox(height: 24),

            // Divider com "OU"
            Row(
              children: [
                Expanded(child: Divider()),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 12),
                  child: Text(
                    'OU',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                Expanded(child: Divider()),
              ],
            ),

            SizedBox(height: 24),

            // Código PIX Copy & Paste
            if (pixCopyPaste != null)
              Card(
                elevation: 2,
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(
                        'Copie o código PIX',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey[700],
                        ),
                      ),
                      SizedBox(height: 12),
                      Container(
                        padding: EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.grey[300]!),
                        ),
                        child: Column(
                          children: [
                            SelectableText(
                              pixCopyPaste!,
                              style: TextStyle(
                                fontSize: 12,
                                fontFamily: 'monospace',
                                color: Colors.black87,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      ),
                      SizedBox(height: 12),
                      ElevatedButton.icon(
                        onPressed: copiarCodigoPix,
                        icon: Icon(Icons.copy),
                        label: Text('Copiar Código'),
                        style: ElevatedButton.styleFrom(
                          padding: EdgeInsets.symmetric(
                            horizontal: 24,
                            vertical: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              SizedBox.shrink(),

            SizedBox(height: 24),

            // Status de polling
            if (isPolling)
              Container(
                padding: EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.blue[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue[200]!),
                ),
                child: Row(
                  children: [
                    SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation(Colors.blue),
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Aguardando pagamento...',
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: Colors.blue[900],
                            ),
                          ),
                          SizedBox(height: 4),
                          Text(
                            'Confirmaremos em segundos após o pagamento',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.blue[700],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              )
            else
              SizedBox.shrink(),

            SizedBox(height: 16),

            // Timer de expiração
            if (remainingSeconds > 0)
              Container(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.schedule,
                      size: 16,
                      color: remainingSeconds < 300
                          ? Colors.red
                          : Colors.grey[600],
                    ),
                    SizedBox(width: 8),
                    Text(
                      'Expira em: ${formatarTempo(remainingSeconds)}',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: remainingSeconds < 300
                            ? Colors.red
                            : Colors.grey[700],
                      ),
                    ),
                  ],
                ),
              )
            else
              SizedBox.shrink(),

            SizedBox(height: 32),

            // Botões de ação
            Column(
              children: [
                ElevatedButton(
                  onPressed: gerarPixTransparente,
                  child: Text('Gerar Novo PIX'),
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
                SizedBox(height: 12),
                OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text('Cancelar'),
                  style: OutlinedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ],
            ),

            SizedBox(height: 24),

            // Instruções
            _buildInstrucoes(),
          ],
        ),
      ),
    );
  }

  /// Widget de instruções
  Widget _buildInstrucoes() {
    return Card(
      color: Colors.amber[50],
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.info_outline, color: Colors.amber[800], size: 20),
                SizedBox(width: 8),
                Text(
                  'Como Pagar',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.amber[900],
                  ),
                ),
              ],
            ),
            SizedBox(height: 12),
            Text(
              '1. Abra o app do seu banco\n'
              '2. Procure pela opção "Pix" ou "Transferência"\n'
              '3. Escolha escanear QR Code ou colar o código\n'
              '4. Confirme o valor: R\$ ${widget.amount.toStringAsFixed(2)}\n'
              '5. Pronto! Seu acesso será liberado automaticamente',
              style: TextStyle(
                fontSize: 13,
                height: 1.8,
                color: Colors.amber[900],
              ),
            ),
            SizedBox(height: 12),
            Text(
              '💡 Você tem até ${formatarTempo(remainingSeconds)} para pagar',
              style: TextStyle(
                fontSize: 12,
                color: Colors.amber[800],
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
