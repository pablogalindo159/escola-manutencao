import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/course_provider.dart';
import '../../models/course_model.dart';
import 'secure_video_player.dart';

class VideoPlayerScreen extends StatefulWidget {
  final int courseId;
  final int videoId;

  const VideoPlayerScreen({
    Key? key,
    required this.courseId,
    required this.videoId,
  }) : super(key: key);

  @override
  State<VideoPlayerScreen> createState() => _VideoPlayerScreenState();
}

class _VideoPlayerScreenState extends State<VideoPlayerScreen> {
  bool _isInitialized = false;
  bool _progressLoaded = false;
  String? _errorMessage;
  Video? _video;
  int _restartCounter = 0;

  @override
  void initState() {
    super.initState();
    _loadVideoInfo();
    _loadProgress();
  }

  Future<void> _loadProgress() async {
    final progressProvider = context.read<VideoProgressProvider>();
    await progressProvider.loadCourseProgress(widget.courseId);
    if (mounted) {
      setState(() {
        _progressLoaded = true;
      });
    }
  }

  Future<void> _loadVideoInfo() async {
    try {
      final courseProvider = context.read<CourseProvider>();
      await courseProvider.selectCourse(widget.courseId);

      final videoData = courseProvider.selectedCourse?.videos
          ?.firstWhere((v) => v.id == widget.videoId);

      if (videoData == null) {
        setState(() {
          _errorMessage = 'Vídeo não encontrado';
        });
        return;
      }

      setState(() {
        _video = videoData;
        _isInitialized = true;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Erro ao carregar vídeo: $e';
      });
    }
  }

  Future<void> _confirmRestart() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Reiniciar aula?'),
        content: const Text('O vídeo vai voltar pro início.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Reiniciar'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      setState(() {
        _restartCounter++;
      });
    }
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_errorMessage != null) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Erro ao carregar vídeo'),
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.error_outline,
                size: 64,
                color: Colors.red,
              ),
              const SizedBox(height: 16),
              Text(_errorMessage!),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () {
                  setState(() {
                    _errorMessage = null;
                  });
                  _loadVideoInfo();
                },
                icon: const Icon(Icons.refresh),
                label: const Text('Tentar novamente'),
              ),
            ],
          ),
        ),
      );
    }

    if (!_isInitialized || !_progressLoaded) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Carregando vídeo...'),
        ),
        body: const Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Assista a aula'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
        actions: [
          IconButton(
            onPressed: _confirmRestart,
            icon: const Icon(Icons.replay),
            tooltip: 'Reiniciar aula',
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Video Player (protegido, com token de curta duração)
            // Importante: o player ocupa a caixa 16:9 inteira - nenhum
            // título/aviso vai aqui dentro, senão o vídeo renderiza menor
            // do que devia (bug corrigido).
            Builder(builder: (context) {
              final progressProvider = context.watch<VideoProgressProvider>();
              final watched = _restartCounter > 0
                  ? 0
                  : progressProvider.getWatchedSeconds(widget.videoId);
              return AspectRatio(
                aspectRatio: 16 / 9,
                child: SecureVideoPlayer(
                  // Muda a key pra forçar recriação do player do zero ao reiniciar
                  key: ValueKey('video-${widget.videoId}-restart-$_restartCounter'),
                  videoId: widget.videoId.toString(),
                  videoTitle: _video?.title ?? 'Aula',
                  initialPositionSeconds: watched,
                  onProgress: (seconds) {
                    context.read<VideoProgressProvider>().updateProgress(
                          videoId: widget.videoId,
                          watchedSeconds: seconds,
                        );
                  },
                ),
              );
            }),

            // Aviso de "continuando de onde parou", se aplicável
            Builder(builder: (context) {
              final progressProvider = context.watch<VideoProgressProvider>();
              final isCompleted = progressProvider.isVideoCompleted(widget.videoId);
              final watched = progressProvider.getWatchedSeconds(widget.videoId);
              if (_restartCounter > 0 || watched < 10) {
                return const SizedBox.shrink();
              }
              final minutes = watched ~/ 60;
              final seconds = watched % 60;
              return Container(
                width: double.infinity,
                color: isCompleted ? Colors.green[50] : Colors.blue[50],
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                child: Row(
                  children: [
                    Icon(
                      isCompleted ? Icons.check_circle : Icons.play_circle_outline,
                      size: 16,
                      color: isCompleted ? Colors.green[700] : const Color(0xFF0066FF),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        isCompleted
                            ? 'Você já concluiu esta aula'
                            : 'Continuando de $minutes:${seconds.toString().padLeft(2, '0')}',
                        style: TextStyle(
                          fontSize: 12,
                          color: isCompleted ? Colors.green[800] : const Color(0xFF0066FF),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),

            // Aviso de proteção (fora da caixa do vídeo, não afeta o tamanho dele)
            Container(
              width: double.infinity,
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

            // Video Info
            Padding(
              padding: const EdgeInsets.all(24),
              child: Consumer<CourseProvider>(
                builder: (context, provider, _) {
                  final course = provider.selectedCourse;
                  if (course == null) return const SizedBox.shrink();

                  final video = course.videos
                      ?.firstWhere((v) => v.id == widget.videoId);

                  if (video == null) return const SizedBox.shrink();

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Title
                      Text(
                        video.title,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Poppins',
                        ),
                      ),
                      const SizedBox(height: 8),

                      // Duration
                      Row(
                        children: [
                          Icon(
                            Icons.timer_outlined,
                            size: 16,
                            color: Colors.grey[600],
                          ),
                          const SizedBox(width: 4),
                          Text(
                            video.durationFormatted,
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey[600],
                              fontFamily: 'Inter',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Description
                      if (video.description != null) ...[
                        const Text(
                          'Descrição',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Poppins',
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          video.description!,
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[700],
                            fontFamily: 'Inter',
                            height: 1.6,
                          ),
                        ),
                        const SizedBox(height: 20),
                      ],

                      // Material
                      if (video.materialUrl != null)
                        ElevatedButton.icon(
                          onPressed: () {
                            // TODO: Download material
                          },
                          icon: const Icon(Icons.download),
                          label: const Text('Baixar Material'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF0066FF),
                          ),
                        ),
                    ],
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
