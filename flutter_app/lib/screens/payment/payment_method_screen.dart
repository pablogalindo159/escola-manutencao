import 'package:flutter/material.dart';
import '../../models/course_model.dart';

class PaymentMethodScreen extends StatelessWidget {
  final Course course;

  const PaymentMethodScreen({
    Key? key,
    required this.course,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Método de Pagamento'),
        backgroundColor: Colors.blue[700],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Resumo do curso
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Curso: ${course.title}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Valor: R\$ ${course.price.toStringAsFixed(2)}',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Colors.green,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Escolha o método de pagamento:',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            // PIX Transparente
            _buildPaymentMethodButton(
              context,
              icon: Icons.qr_code,
              title: 'PIX Transparente',
              subtitle: 'Escaneie o QR Code ou copie o código',
              onTap: () {
                Navigator.pushNamed(
                  context,
                  '/pix-transparente',
                  arguments: {
                    'course': course,
                    'amount': course.price,
                  },
                );
              },
            ),
            const SizedBox(height: 12),
            // Checkout Pro
            _buildPaymentMethodButton(
              context,
              icon: Icons.credit_card,
              title: 'Mercado Pago Checkout',
              subtitle: 'Cartão, boleto, dinheiro e mais',
              onTap: () {
                Navigator.pushNamed(
                  context,
                  '/checkout-pro',
                  arguments: {
                    'course': course,
                    'amount': course.price,
                  },
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaymentMethodButton(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Material(
      child: InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.grey[300]!),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              Icon(icon, size: 32, color: Colors.blue[700]),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.arrow_forward_ios,
                size: 16,
                color: Colors.grey[400],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
