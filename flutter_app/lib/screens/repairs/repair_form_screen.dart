import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/repair_provider.dart';

class RepairFormScreen extends StatefulWidget {
  const RepairFormScreen({Key? key}) : super(key: key);

  @override
  State<RepairFormScreen> createState() => _RepairFormScreenState();
}

class _RepairFormScreenState extends State<RepairFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _equipmentTypeController = TextEditingController();
  final _customerNameController = TextEditingController();
  final _defectDescriptionController = TextEditingController();
  final _diagnosisController = TextEditingController();
  final _notesController = TextEditingController();
  String _selectedEquipmentType = 'Celular';

  final List<String> _equipmentTypes = [
    'Celular',
    'Notebook',
    'Tablet',
    'Impressora',
    'Desktop',
    'Monitor',
    'Teclado',
    'Mouse',
    'Webcam',
    'Outro',
  ];

  @override
  void dispose() {
    _equipmentTypeController.dispose();
    _customerNameController.dispose();
    _defectDescriptionController.dispose();
    _diagnosisController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    final provider = context.read<RepairProvider>();
    final success = await provider.createRepair(
      equipmentType: _selectedEquipmentType,
      customerName: _customerNameController.text.trim(),
      defectDescription: _defectDescriptionController.text.trim(),
      diagnosis: _diagnosisController.text.isNotEmpty
          ? _diagnosisController.text.trim()
          : null,
      notes: _notesController.text.isNotEmpty
          ? _notesController.text.trim()
          : null,
    );

    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Reparo criado com sucesso!'),
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
        title: const Text('Novo Reparo'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Equipment Type
              const Text(
                'Tipo de Equipamento',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Poppins',
                ),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _selectedEquipmentType,
                items: _equipmentTypes.map((type) {
                  return DropdownMenuItem(
                    value: type,
                    child: Text(type),
                  );
                }).toList(),
                onChanged: (value) {
                  setState(() {
                    _selectedEquipmentType = value!;
                  });
                },
                decoration: InputDecoration(
                  hintText: 'Selecione o equipamento',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 14,
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Customer Name (Optional)
              TextFormField(
                controller: _customerNameController,
                decoration: InputDecoration(
                  labelText: 'Nome do Cliente (opcional)',
                  hintText: 'Ex: João Silva',
                  prefixIcon: const Icon(Icons.person_outlined),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(
                      color: Color(0xFF0066FF),
                      width: 2,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Defect Description (Required)
              TextFormField(
                controller: _defectDescriptionController,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: 'Descrição do Defeito',
                  hintText: 'Descreva o problema encontrado...',
                  prefixIcon: Align(
                    alignment: Alignment.topLeft,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Icon(Icons.description_outlined),
                    ),
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(
                      color: Color(0xFF0066FF),
                      width: 2,
                    ),
                  ),
                ),
                validator: (value) {
                  if (value?.isEmpty ?? true) {
                    return 'Descrição do defeito é obrigatória';
                  }
                  if (value!.length < 10) {
                    return 'Descrição deve ter no mínimo 10 caracteres';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),

              // Diagnosis (Optional)
              TextFormField(
                controller: _diagnosisController,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: 'Diagnóstico Inicial (opcional)',
                  hintText: 'Sua análise do problema...',
                  prefixIcon: Align(
                    alignment: Alignment.topLeft,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Icon(Icons.analytics_outlined),
                    ),
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(
                      color: Color(0xFF0066FF),
                      width: 2,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // Notes (Optional)
              TextFormField(
                controller: _notesController,
                maxLines: 2,
                decoration: InputDecoration(
                  labelText: 'Notas Adicionais (opcional)',
                  hintText: 'Qualquer informação extra...',
                  prefixIcon: Align(
                    alignment: Alignment.topLeft,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Icon(Icons.note_outlined),
                    ),
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(
                      color: Color(0xFF0066FF),
                      width: 2,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 32),

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
                child: Row(
                  children: [
                    const Icon(
                      Icons.info_outlined,
                      color: Color(0xFF0066FF),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Próximas etapas',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF0066FF),
                              fontFamily: 'Poppins',
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Após criar, você poderá adicionar fotos (antes/durante/depois) e enviar para análise.',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[700],
                              fontFamily: 'Inter',
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              // Submit Button
              Consumer<RepairProvider>(
                builder: (context, provider, _) {
                  return SizedBox(
                    width: double.infinity,
                    height: 56,
                    child: ElevatedButton(
                      onPressed: provider.isLoading ? null : _submitForm,
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
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                                strokeWidth: 2,
                              ),
                            )
                          : const Text(
                              'Criar Reparo',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                                fontFamily: 'Poppins',
                              ),
                            ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 16),

              // Cancel Button
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
          ),
        ),
      ),
    );
  }
}
