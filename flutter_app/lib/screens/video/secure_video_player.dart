import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/api_service.dart';

/// Player de vídeo seguro: busca uma URL de stream com token de curta
/// duração no backend (proteção contra compartilhamento de link/conta) e
/// força novo login quando o token expira.
class SecureVideoPlayer extends StatefulWidget {
  final String videoId;
  final String videoTitle;

  const SecureVideoPlayer({
    Key? key,
    required this.videoId,
    required this.videoTitle,
  }) : super(key: key);

  @override
  State<SecureVideoPlayer> createState() => _SecureVideoPlayerState();
}

class _SecureVideoPlayerState extends State<SecureVideoPlayer> {
  VideoPlayerController? _videoController;
  ChewieController? _chewieController;
  bool _isLoading = true;
  String? _errorMessage;
  String? _expiresAt;
  bool _isYoutube = false;
  String? _youtubeId;

  final _dio = Dio();
  final _secureStorage = const FlutterSecureStorage();

  @override
  void initState() {
    super.initState();
    _initializeVideo();
  }

  Future<void> _initializeVideo() async {
    try {
      final token = await _secureStorage.read(key: 'auth_token') ?? '';

      if (token.isEmpty) {
        _setError('Sessão expirada. Faça login novamente.');
        return;
      }

      final response = await _dio.get(
        '${ApiService.baseUrl}/videos/${widget.videoId}/stream-url',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
          validateStatus: (status) => status != null,
        ),
      );

      if (response.statusCode != 200) {
        _setError(response.data['message'] ?? 'Erro ao carregar vídeo');
        return;
      }

      final data = response.data['data'];
      final streamUrl = data['stream_url'] as String;
      final expiresIn = data['expires_in'] as int;
      _expiresAt = data['expires_at'] as String?;

      if (data['is_youtube'] == true) {
        _isYoutube = true;
        _youtubeId = data['youtube_id'] as String?;

        if (!mounted) return;
        setState(() {
          _isLoading = false;
        });
        return;
      }

      _videoController = VideoPlayerController.networkUrl(
        Uri.parse(streamUrl),
        httpHeaders: {'Authorization': 'Bearer $token'},
      );

      await _videoController!.initialize();

      _chewieController = ChewieController(
        videoPlayerController: _videoController!,
        aspectRatio: _videoController!.value.aspectRatio,
        autoPlay: false,
        looping: false,
        showControls: true,
      );

      if (!mounted) return;
      setState(() {
        _isLoading = false;
      });

      _startExpirationTimer(expiresIn);
    } catch (e) {
      _setError('Erro ao carregar vídeo: $e');
    }
  }

  void _startExpirationTimer(int expiresIn) {
    final warnIn = expiresIn - 60;
    if (warnIn > 0) {
      Future.delayed(Duration(seconds: warnIn), () {
        if (mounted) _showExpirationWarning();
      });
    }

    Future.delayed(Duration(seconds: expiresIn), () {
      if (mounted) {
        _videoController?.pause();
        _handleTokenExpired();
      }
    });
  }

  void _showExpirationWarning() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        backgroundColor: Colors.grey[900],
        title: const Text(
          '⏰ Sua sessão expira em breve',
          style: TextStyle(color: Colors.white),
        ),
        content: const Text(
          'Seu acesso a este vídeo expira em cerca de 1 minuto.\n\n'
          'Você precisará abrir o vídeo novamente depois.',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _handleTokenExpired() {
    _videoController?.pause();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        backgroundColor: Colors.grey[900],
        title: const Text(
          '🔐 Sessão de vídeo expirada',
          style: TextStyle(color: Colors.red, fontSize: 18, fontWeight: FontWeight.bold),
        ),
        content: const Text(
          'O acesso a este vídeo expirou por segurança.\n\n'
          'Toque em "Voltar" e abra o vídeo novamente para continuar assistindo.',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF0066FF)),
            onPressed: () {
              Navigator.pop(context);
              Navigator.pop(context);
            },
            child: const Text('Voltar'),
          ),
        ],
      ),
    );
  }

  void _setError(String message) {
    if (!mounted) return;
    setState(() {
      _isLoading = false;
      _errorMessage = message;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.black,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.videoTitle,
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                ),
                if (_expiresAt != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      'Acesso válido por tempo limitado',
                      style: TextStyle(color: Colors.white.withOpacity(0.6), fontSize: 12),
                    ),
                  ),
              ],
            ),
          ),
          Expanded(
            child: Container(
              color: Colors.black,
              child: _isLoading
                  ? const Center(
                      child: CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(Colors.white)),
                    )
                  : _errorMessage != null
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(24),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.error_outline, color: Colors.red[400], size: 48),
                                const SizedBox(height: 16),
                                Text(
                                  _errorMessage!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(color: Colors.white, fontSize: 16),
                                ),
                              ],
                            ),
                          ),
                        )
                      : _isYoutube
                          ? Center(
                              child: Padding(
                                padding: const EdgeInsets.all(24),
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Icon(Icons.smart_display_outlined, color: Colors.white, size: 56),
                                    const SizedBox(height: 16),
                                    const Text(
                                      'Este vídeo está hospedado no YouTube',
                                      textAlign: TextAlign.center,
                                      style: TextStyle(color: Colors.white, fontSize: 14),
                                    ),
                                    const SizedBox(height: 20),
                                    ElevatedButton.icon(
                                      onPressed: () async {
                                        if (_youtubeId == null) return;
                                        final uri = Uri.parse('https://www.youtube.com/watch?v=$_youtubeId');
                                        if (await canLaunchUrl(uri)) {
                                          await launchUrl(uri, mode: LaunchMode.externalApplication);
                                        }
                                      },
                                      icon: const Icon(Icons.play_circle_outline),
                                      label: const Text('Assistir no YouTube'),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: const Color(0xFFDC2626),
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            )
                          : Chewie(controller: _chewieController!),
            ),
          ),
          Container(
            color: Colors.grey[900],
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Icon(Icons.shield_outlined, color: Colors.blue[400], size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Este vídeo é protegido. O link de acesso expira automaticamente.',
                    style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _chewieController?.dispose();
    _videoController?.dispose();
    super.dispose();
  }
}
