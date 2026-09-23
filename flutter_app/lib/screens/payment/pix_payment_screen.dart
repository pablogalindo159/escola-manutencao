import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../../services/api_service.dart';

/// PIX Transparente dentro do app (mesma lógica do site):
/// gera o PIX no backend, mostra QR Code + copia-e-cola e consulta
/// o status a cada 5s. O webhook libera o curso no servidor; aqui só
/// mostramos quando ficou aprovado.
class PixPaymentScreen extends StatefulWidget {
  final int courseId;
  final String courseTitle;
  final double price;

  const PixPaymentScreen({
    Key? key,
    required this.courseId,
    required this.courseTitle,
    required this.price,
  }) : super(key: key);

  @override
  State<PixPaymentScreen> createState() => _PixPaymentScreenState();
}

class _PixPaymentScreenState extends State<PixPaymentScreen> {
  final ApiService _api = ApiService();
  Timer? _timer;

  bool _loading = true;
  bool _approved = false;
  bool _checking = false;
  String? _error;
  int? _paymentId;
  String? _qrCode;
  String? _qrBase64;

  @override
  void initState() {
    super.initState();
    _gerarPix();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _gerarPix() async {
    _timer?.cancel();
    setState(() {
      _loading = true;
      _error = null;
      _qrCode = null;
      _qrBase64 = null;
      _paymentId = null;
    });

    try {
      final data = await _api.createPix(widget.courseId);
      if (!mounted) return;
      setState(() {
        _paymentId = (data['payment_id'] as num?)?.toInt();
        _qrCode = data['qr_code'] as String?;
        _qrBase64 = data['qr_code_base64'] as String?;
        _loading = false;
      });
      _timer = Timer.periodic(
        const Duration(seconds: 5),
        (_) => _verificarStatus(),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _verificarStatus() async {
    if (_paymentId == null || _checking || _approved) return;
    _checking = true;
    try {
      final status = await _api.getPaymentStatus(_paymentId!);
      if (!mounted) return;
      if (status == 'approved') {
        _timer?.cancel();
        setState(() => _approved = true);
      } else if (status == 'rejected' || status == 'cancelled') {
        _timer?.cancel();
        setState(() => _error = 'Pagamento não aprovado. Gere um novo PIX.');
      }
    } catch (_) {
      // Falha de rede momentânea: tenta de novo no próximo ciclo.
    } finally {
      _checking = false;
    }
  }

  Future<void> _copiarCodigo() async {
    final code = _qrCode;
    if (code == null) return;
    await Clipboard.setData(ClipboardData(text: code));
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Código PIX copiado!'),
        backgroundColor: Colors.green,
      ),
    );
  }

  Widget _qrWidget() {
    final b64 = _qrBase64;
    if (b64 != null && b64.isNotEmpty) {
      try {
        final raw = b64.contains(',') ? b64.split(',').last : b64;
        return Image.memory(base64Decode(raw), width: 240, height: 240);
      } catch (_) {
        // Se a imagem vier inválida, desenha o QR a partir do código.
      }
    }
    return QrImageView(
      data: _qrCode ?? '',
      version: QrVersions.auto,
      size: 240,
      backgroundColor: Colors.white,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pagamento via PIX'),
      ),
      body: SafeArea(child: _buildBody()),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text('Gerando PIX...'),
          ],
        ),
      );
    }

    if (_approved) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.check_circle, color: Colors.green, size: 96),
              const SizedBox(height: 16),
              const Text(
                'Pagamento aprovado!',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              const Text(
                'Seu acesso ao curso foi liberado.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('Acessar curso'),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (_qrCode == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 64),
              const SizedBox(height: 16),
              Text(
                _error ?? 'Não foi possível gerar o PIX.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: _gerarPix,
                child: const Text('Tentar novamente'),
              ),
            ],
          ),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            widget.courseTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            'R\$ ${widget.price.toStringAsFixed(2)}',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Color(0xFF0066FF),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.red[50],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(_error!, style: TextStyle(color: Colors.red[800])),
            ),
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: _gerarPix,
              child: const Text('Gerar novo PIX'),
            ),
          ],
          const SizedBox(height: 20),
          Center(
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey[300]!),
              ),
              child: _qrWidget(),
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Abra o app do seu banco, escolha pagar com PIX e escaneie o QR Code, ou copie o código abaixo.',
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              _qrCode!,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, fontFamily: 'monospace'),
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 50,
            child: ElevatedButton.icon(
              onPressed: _copiarCodigo,
              icon: const Icon(Icons.copy),
              label: const Text('Copiar código PIX'),
            ),
          ),
          const SizedBox(height: 24),
          if (_error == null)
            const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
                SizedBox(width: 8),
                Text('Aguardando pagamento...'),
              ],
            ),
          const SizedBox(height: 8),
          TextButton(
            onPressed: _verificarStatus,
            child: const Text('Já paguei — verificar agora'),
          ),
        ],
      ),
    );
  }
}
