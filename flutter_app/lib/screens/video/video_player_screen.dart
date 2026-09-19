import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:youtube_player_flutter/youtube_player_flutter.dart';
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

  // Player do YouTube (embutido no app - nunca abre externamente)
  YoutubePlayerController? _youtubeController;
  Timer? _youtubeProgressTimer;

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

  YoutubePlayerController _getYoutubeController(String youtubeId) {
    if (_youtubeController == null) {
      final progressProvider = context.read<VideoProgressProvider>();
      final startAt = progressProvider.getWatchedSeconds(widget.videoId);

      _youtubeController = YoutubePlayerController(
        initialVideoId: youtubeId,
        flags: YoutubePlayerFlags(
          autoPlay: false,
          startAt: startAt,
        ),
      );

      _youtubeProgressTimer = Timer.periodic(const Duration(seconds: 10), (_) {
        final position = _youtubeController?.value.position;
        if (position != null) {
          context.read<VideoProgressProvider>().updateProgress(
                videoId: widget.videoId,
                watchedSeconds: position.inSeconds,
              );
        }
      });
    }
    return _youtubeController!;
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
      _youtubeController?.seekTo(const Duration());
      _youtubeController?.play();
    }
  }

  @override
  void dispose() {
    _youtubeProgressTimer?.cancel();
    _youtubeController?.dispose();
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

    final youtubeId = _video?.youtubeId;
    if (youtubeId != null) {
      return _buildYoutubeScaffold(youtubeId);
    }
    return _buildDirectVideoScaffold();
  }

  // ==================== Vídeo do YouTube (embutido) ====================

  Widget _buildYoutubeScaffold(String youtubeId) {
    final controller = _getYoutubeController(youtubeId);

    return YoutubePlayerBuilder(
      player: YoutubePlayer(
        controller: controller,
        showVideoProgressIndicator: true,
        progressIndicatorColor: const Color(0xFF0066FF),
        bottomActions: const [
          CurrentPosition(),
          SizedBox(width: 8),
          ProgressBar(isExpanded: true),
          RemainingDuration(),
          FullScreenButton(),
        ],
      ),
      builder: (context, player) {
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
                player,
                _buildContinueBanner(),
                _buildVideoInfoAndLessons(),
              ],
            ),
          ),
        );
      },
    );
  }

  // ==================== Vídeo direto (arquivo/CDN, player seguro) ====================

  Widget _buildDirectVideoScaffold() {
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

            _buildContinueBanner(),

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

            _buildVideoInfoAndLessons(),
          ],
        ),
      ),
    );
  }

  // ==================== Blocos compartilhados pelos dois players ====================

  /// Aviso de "continuando de onde parou", se aplicável
  Widget _buildContinueBanner() {
    return Builder(builder: (context) {
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
    });
  }

  Widget _buildVideoInfoAndLessons() {
    return Padding(
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

              // Lista de aulas do curso (anterior/próxima), com a atual destacada
              if (course.videos != null && course.videos!.length > 1) ...[
                const SizedBox(height: 28),
                const Divider(),
                const SizedBox(height: 16),
                const Text(
                  'Aulas do Curso',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    fontFamily: 'Poppins',
                  ),
                ),
                const SizedBox(height: 12),
                Consumer<VideoProgressProvider>(
                  builder: (context, progressProvider, _) {
                    return ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: course.videos!.length,
                      itemBuilder: (context, index) {
                        final v = course.videos![index];
                        final isCurrent = v.id == widget.videoId;
                        final isCompleted =
                            progressProvider.isVideoCompleted(v.id);
                        final isStarted =
                            progressProvider.isVideoStarted(v.id);

                        return InkWell(
                          borderRadius: BorderRadius.circular(8),
                          onTap: isCurrent
                              ? null
                              : () {
                                  Navigator.of(context).pushReplacement(
                                    MaterialPageRoute(
                                      builder: (_) => VideoPlayerScreen(
                                        courseId: widget.courseId,
                                        videoId: v.id,
                                      ),
                                    ),
                                  );
                                },
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: isCurrent
                                  ? const Color(0xFF0066FF).withOpacity(0.08)
                                  : Colors.grey[100],
                              borderRadius: BorderRadius.circular(8),
                              border: isCurrent
                                  ? Border.all(color: const Color(0xFF0066FF))
                                  : null,
                            ),
                            child: Row(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(6),
                                  child: v.thumbnailUrl != null &&
                                          v.thumbnailUrl!.isNotEmpty
                                      ? Image.network(
                                          v.thumbnailUrl!,
                                          width: 56,
                                          height: 42,
                                          fit: BoxFit.cover,
                                          errorBuilder: (_, __, ___) =>
                                              _videoThumbnailFallback(),
                                        )
                                      : _videoThumbnailFallback(),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${index + 1}. ${v.title}',
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: isCurrent
                                              ? FontWeight.bold
                                              : FontWeight.w600,
                                          fontFamily: 'Poppins',
                                          color: isCurrent
                                              ? const Color(0xFF0066FF)
                                              : Colors.black87,
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      Row(
                                        children: [
                                          Text(
                                            v.durationFormatted,
                                            style: TextStyle(
                                              fontSize: 11,
                                              color: Colors.grey[600],
                                              fontFamily: 'Inter',
                                            ),
                                          ),
                                          if (isCurrent) ...[
                                            const SizedBox(width: 6),
                                            const Text(
                                              '· Assistindo agora',
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: Color(0xFF0066FF),
                                                fontFamily: 'Inter',
                                              ),
                                            ),
                                          ] else if (isCompleted) ...[
                                            const SizedBox(width: 6),
                                            Text(
                                              '· Concluído',
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: Colors.green[700],
                                                fontFamily: 'Inter',
                                              ),
                                            ),
                                          ] else if (isStarted) ...[
                                            const SizedBox(width: 6),
                                            const Text(
                                              '· Continuar',
                                              style: TextStyle(
                                                fontSize: 11,
                                                color: Color(0xFF0066FF),
                                                fontFamily: 'Inter',
                                              ),
                                            ),
                                          ],
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                if (isCurrent)
                                  const Icon(
                                    Icons.play_circle_fill,
                                    color: Color(0xFF0066FF),
                                    size: 20,
                                  )
                                else if (isCompleted)
                                  Icon(
                                    Icons.check_circle,
                                    color: Colors.green[600],
                                    size: 18,
                                  ),
                              ],
                            ),
                          ),
                        );
                      },
                    );
                  },
                ),
              ],
            ],
          );
        },
      ),
    );
  }

  Widget _videoThumbnailFallback() {
    return Container(
      width: 56,
      height: 42,
      color: const Color(0xFF0066FF).withOpacity(0.1),
      child: const Icon(
        Icons.play_circle_outline,
        color: Color(0xFF0066FF),
        size: 20,
      ),
    );
  }
}
