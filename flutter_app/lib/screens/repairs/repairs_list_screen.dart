import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/repair_provider.dart';
import '../../models/repair_model.dart';
import '../../widgets/repair_card.dart';

class RepairsListScreen extends StatefulWidget {
  const RepairsListScreen({Key? key}) : super(key: key);

  @override
  State<RepairsListScreen> createState() => _RepairsListScreenState();
}

class _RepairsListScreenState extends State<RepairsListScreen> {
  String _selectedStatus = 'all';
  final List<Map<String, String>> _statuses = [
    {'value': 'all', 'label': 'Todos'},
    {'value': 'draft', 'label': 'Rascunho'},
    {'value': 'pending_review', 'label': 'Análise'},
    {'value': 'approved', 'label': 'Aprovados'},
    {'value': 'rejected', 'label': 'Rejeitados'},
  ];

  @override
  void initState() {
    super.initState();
    _loadRepairs();
  }

  Future<void> _loadRepairs() async {
    final provider = context.read<RepairProvider>();
    final status = _selectedStatus == 'all' ? null : _selectedStatus;
    await provider.loadRepairs(status: status);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Meus Reparos'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outlined),
            onPressed: () {
              Navigator.of(context).pushNamed('/repair-form').then(
                (result) {
                  if (result == true) {
                    _loadRepairs();
                  }
                },
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadRepairs,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                padding: const EdgeInsets.all(24),
                color: const Color(0xFF0066FF),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Acompanhe seus reparos',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        fontFamily: 'Poppins',
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Envie fotos e acompanhe a análise do professor',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white70,
                        fontFamily: 'Inter',
                      ),
                    ),
                  ],
                ),
              ),

              // Status Filter
              SizedBox(
                height: 50,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                  itemCount: _statuses.length,
                  itemBuilder: (context, index) {
                    final status = _statuses[index];
                    final isSelected = status['value'] == _selectedStatus;

                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: FilterChip(
                        label: Text(status['label']!),
                        selected: isSelected,
                        onSelected: (selected) {
                          setState(() {
                            _selectedStatus = status['value']!;
                          });
                          _loadRepairs();
                        },
                        backgroundColor: Colors.transparent,
                        selectedColor: const Color(0xFF0066FF),
                        labelStyle: TextStyle(
                          color: isSelected
                              ? Colors.white
                              : const Color(0xFF666666),
                          fontWeight: isSelected
                              ? FontWeight.w600
                              : FontWeight.normal,
                        ),
                      ),
                    );
                  },
                ),
              ),

              // Repairs List
              Padding(
                padding: const EdgeInsets.all(16),
                child: Consumer<RepairProvider>(
                  builder: (context, provider, _) {
                    if (provider.isLoading) {
                      return ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: 3,
                        itemBuilder: (context, index) {
                          return const Padding(
                            padding: EdgeInsets.only(bottom: 16),
                            child: RepairShimmer(),
                          );
                        },
                      );
                    }

                    if (provider.repairs.isEmpty) {
                      return Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32),
                          child: Column(
                            children: [
                              Icon(
                                Icons.build_outlined,
                                size: 64,
                                color: Colors.grey[300],
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Nenhum reparo encontrado',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.grey[600],
                                  fontFamily: 'Inter',
                                ),
                              ),
                              const SizedBox(height: 24),
                              ElevatedButton.icon(
                                onPressed: () {
                                  Navigator.of(context)
                                      .pushNamed('/repair-form')
                                      .then((result) {
                                    if (result == true) {
                                      _loadRepairs();
                                    }
                                  });
                                },
                                icon: const Icon(Icons.add),
                                label: const Text('Criar novo reparo'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF0066FF),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: provider.repairs.length,
                      itemBuilder: (context, index) {
                        final repair = provider.repairs[index];
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: RepairCard(
                            repair: repair,
                            onTap: () {
                              Navigator.of(context)
                                  .pushNamed(
                                    '/repair-detail',
                                    arguments: repair.id,
                                  )
                                  .then((result) {
                                if (result == true) {
                                  _loadRepairs();
                                }
                              });
                            },
                          ),
                        );
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Shimmer Loading Widget
class RepairShimmer extends StatelessWidget {
  const RepairShimmer({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 160,
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
    );
  }
}
