import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/api_service.dart';

class LiveStreamScreen extends StatefulWidget {
  final int streamId;

  const LiveStreamScreen({
    Key? key,
    required this.streamId,
  }) : super(key: key);

  @override
  State<LiveStreamScreen> createState() => _LiveStreamScreenState();
}

class _LiveStreamScreenState extends State<LiveStreamScreen> {
  final ApiService _apiService = ApiService();
  Map<String, dynamic>? _stream;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadStream();
  }

  Future<void> _loadStream() async {
    try {
      final result = await _apiService.getLiveStream(widget.streamId);
      setState(() {
        _stream = result['data'];
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Não foi possível carregar a transmissão';
        _isLoading = false;
      });
    }
  }

  Future<void> _openYoutube(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'live':
        return '🔴 AO VIVO AGORA';
      case 'scheduled':
        return '📅 Agendada';
      case 'ended':
        return '⏸️ Finalizada';
      case 'archived':
        return '✅ Arquivada';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Transmissão ao Vivo'),
        backgroundColor: const Color(0xFFDC2626),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.error_outline, size: 48, color: Colors.red),
                        const SizedBox(height: 16),
                        Text(_errorMessage!, textAlign: TextAlign.center),
                      ],
                    ),
                  ),
                )
              : _buildContent(),
    );
  }

  Widget _buildContent() {
    final stream = _stream!;
    final status = stream['status'] as String;
    final youtubeVideoId = stream['youtube_video_id'] as String?;
    final scheduledAt = DateTime.tryParse(stream['scheduled_at'] ?? '');

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            color: status == 'live' ? const Color(0xFFDC2626) : const Color(0xFF6B7280),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _statusLabel(status),
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                ),
                if (scheduledAt != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    DateFormat('dd/MM/yyyy HH:mm').format(scheduledAt),
                    style: const TextStyle(color: Colors.white70),
                  ),
                ],
              ],
            ),
          ),

          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  stream['title'] ?? '',
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                if (stream['description'] != null)
                  Text(
                    stream['description'],
                    style: TextStyle(fontSize: 14, color: Colors.grey[700], height: 1.5),
                  ),
                const SizedBox(height: 20),

                if (youtubeVideoId != null && youtubeVideoId.isNotEmpty)
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => _openYoutube('https://www.youtube.com/watch?v=$youtubeVideoId'),
                      icon: const Icon(Icons.play_circle_outline),
                      label: Text(status == 'live' ? 'Assistir Agora no YouTube' : 'Ver no YouTube'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                    ),
                  )
                else
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Text(
                      'O link da transmissão ainda não foi configurado. Volte mais perto do horário agendado.',
                      style: TextStyle(color: Colors.grey),
                    ),
                  ),

                const SizedBox(height: 24),
                Row(
                  children: [
                    _statBox(
                      status == 'live' ? '${stream['viewers_count'] ?? 0}' : '${stream['total_viewers'] ?? 0}',
                      status == 'live' ? 'assistindo agora' : 'assistiram',
                    ),
                    const SizedBox(width: 16),
                    _statBox('${stream['likes'] ?? 0}', 'curtidas'),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _statBox(String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            Text(label, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
          ],
        ),
      ),
    );
  }
}
