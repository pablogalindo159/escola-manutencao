import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'dart:io';
import '../providers/repair_provider.dart';

class RepairPhotoScreen extends StatefulWidget {
  final int repairId;

  const RepairPhotoScreen({
    Key? key,
    required this.repairId,
  }) : super(key: key);

  @override
  State<RepairPhotoScreen> createState() => _RepairPhotoScreenState();
}

class _RepairPhotoScreenState extends State<RepairPhotoScreen> {
  final _imagePicker = ImagePicker();
  final Map<String, File?> _photos = {
    'before': null,
    'during': null,
    'after': null,
    'diagnostic': null,
  };

  final Map<String, String> _stageLabels = {
    'before': 'Antes (Before)',
    'during': 'Durante (During)',
    'after': 'Depois (After)',
    'diagnostic': 'Diagnóstico (Diagnostic)',
  };

  Future<void> _pickImage(String stage) async {
    try {
      final pickedFile = await _imagePicker.pickImage(
        source: ImageSource.camera,
        imageQuality: 85, // Compressão automática
      );

      if (pickedFile != null) {
        setState(() {
          _photos[stage] = File(pickedFile.path);
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erro ao capturar foto: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _submitPhotos() async {
    // Validar que pelo menos uma foto foi tirada
    final hasPhotos = _photos.values.any((p) => p != null);
    if (!hasPhotos) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Adicione pelo menos uma foto'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final provider = context.read<RepairProvider>();

    // Upload de cada foto
    for (final entry in _photos.entries) {
      if (entry.value != null) {
        final success = await provider.uploadRepairPhoto(
          repairId: widget.repairId,
          filePath: entry.value!.path,
          stage: entry.key,
        );

        if (!success && mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content:
                  Text('Erro ao fazer upload de ${_stageLabels[entry.key]}'),
              backgroundColor: Colors.red,
            ),
          );
          return;
        }
      }
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Fotos enviadas com sucesso!'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.of(context).pop(true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Adicionar Fotos'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Info Box
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF0066FF).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFF0066FF).withOpacity(0.3),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    '📸 Adicione 4 Fotos do Reparo',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF0066FF),
                      fontFamily: 'Poppins',
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '• Antes: Estado inicial do equipamento\n• Durante: Processo de reparo\n• Depois: Resultado final\n• Diagnóstico: Detalhes técnicos',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[700],
                      fontFamily: 'Inter',
                      height: 1.6,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Photo Cards
            ..._photos.entries.map((entry) {
              final stage = entry.key;
              final file = entry.value;
              final label = _stageLabels[stage]!;

              return Column(
                children: [
                  _buildPhotoCard(stage, label, file),
                  const SizedBox(height: 16),
                ],
              );
            }).toList(),

            // Progress
            const SizedBox(height: 8),
            _buildProgressBar(),
            const SizedBox(height: 32),

            // Submit Button
            Consumer<RepairProvider>(
              builder: (context, provider, _) {
                return Column(
                  children: [
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton(
                        onPressed: provider.isLoading ? null : _submitPhotos,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF0066FF),
                          disabledBackgroundColor: const Color(0xFFCCCCCC),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: provider.isLoading
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  valueColor:
                                      AlwaysStoppedAnimation<Color>(
                                    Colors.white,
                                  ),
                                  strokeWidth: 2,
                                ),
                              )
                            : const Text(
                                'Enviar Fotos',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                  fontFamily: 'Poppins',
                                ),
                              ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.of(context).pop();
                        },
                        style: OutlinedButton.styleFrom(
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Text(
                          'Cancelar',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF0066FF),
                            fontFamily: 'Poppins',
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPhotoCard(String stage, String label, File? file) {
    return GestureDetector(
      onTap: () => _pickImage(stage),
      child: Container(
        height: 200,
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: file != null ? const Color(0xFF51CF66) : Colors.grey[300]!,
            width: file != null ? 2 : 1,
          ),
        ),
        child: Stack(
          children: [
            // Image or Placeholder
            if (file != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.file(
                  file,
                  fit: BoxFit.cover,
                  width: double.infinity,
                  height: double.infinity,
                ),
              )
            else
              Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.camera_alt_outlined,
                      size: 48,
                      color: Colors.grey[400],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Tocar para capturar',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[600],
                        fontFamily: 'Inter',
                      ),
                    ),
                  ],
                ),
              ),

            // Label
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [
                      Colors.black.withOpacity(0.7),
                      Colors.transparent,
                    ],
                  ),
                  borderRadius: const BorderRadius.only(
                    bottomLeft: Radius.circular(12),
                    bottomRight: Radius.circular(12),
                  ),
                ),
                padding: const EdgeInsets.all(12),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      label,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                        fontFamily: 'Poppins',
                      ),
                    ),
                    if (file != null)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFF51CF66),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Icon(
                          Icons.check,
                          color: Colors.white,
                          size: 16,
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProgressBar() {
    final photosCount =
        _photos.values.where((p) => p != null).length;
    final percentage = (photosCount / 4 * 100).toStringAsFixed(0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'Progresso',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                fontFamily: 'Inter',
                color: Colors.grey,
              ),
            ),
            Text(
              '$photosCount/4 fotos',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                fontFamily: 'Poppins',
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: LinearProgressIndicator(
            value: photosCount / 4,
            minHeight: 8,
            valueColor: const AlwaysStoppedAnimation<Color>(
              Color(0xFF51CF66),
            ),
            backgroundColor: Colors.grey[200],
          ),
        ),
      ],
    );
  }
}
