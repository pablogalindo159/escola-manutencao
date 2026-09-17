import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';
import 'package:provider/provider.dart';
import '../providers/course_provider.dart';
import '../models/course_model.dart';

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
  late VideoPlayerController _videoPlayerController;
  late ChewieController _chewieController;
  bool _isInitialized = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _initializePlayer();
  }

  Future<void> _initializePlayer() async {
    try {
      final courseProvider = context.read<CourseProvider>();
      final video = await courseProvider.selectCourse(widget.courseId);

      // Encontrar o vídeo específico
      final videoData = courseProvider.selectedCourse?.videos
          ?.firstWhere((v) => v.id == widget.videoId);

      if (videoData == null) {
        setState(() {
          _errorMessage = 'Vídeo não encontrado';
        });
        return;
      }

      _videoPlayerController =
          VideoPlayerController.network(videoData.videoUrl);

      await _videoPlayerController.initialize();

      _chewieController = ChewieController(
        videoPlayerController: _videoPlayerController,
        autoPlay: true,
        looping: false,
        fullScreenByDefault: false,
        showOptions: true,
        showControls: true,
        allowFullScreen: true,
        allowMuting: true,
        progressIndicatorDelay: const Duration(milliseconds: 300),
        materialProgressColors: ChewieProgressColors(
          playedColor: const Color(0xFF0066FF),
          handleColor: const Color(0xFF0066FF),
          backgroundColor: Colors.grey[300]!,
          bufferedColor: Colors.grey[100]!,
        ),
        startAt: const Duration(),
      );

      setState(() {
        _isInitialized = true;
      });

      // Atualizar progresso a cada 10 segundos
      _videoPlayerController.addListener(_updateProgress);
    } catch (e) {
      setState(() {
        _errorMessage = 'Erro ao carregar vídeo: $e';
      });
    }
  }

  void _updateProgress() {
    if (!_videoPlayerController.value.isInitialized) return;

    final position = _videoPlayerController.value.position.inSeconds;
    final duration = _videoPlayerController.value.duration.inSeconds;

    // Enviar progresso para API a cada 10 segundos
    if (position % 10 == 0) {
      final courseProvider = context.read<CourseProvider>();
      // TODO: Atualizar progresso via provider
    }
  }

  @override
  void dispose() {
    _videoPlayerController.removeListener(_updateProgress);
    _videoPlayerController.dispose();
    _chewieController.dispose();
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
                  _initializePlayer();
                },
                icon: const Icon(Icons.refresh),
                label: const Text('Tentar novamente'),
              ),
            ],
          ),
        ),
      );
    }

    if (!_isInitialized) {
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
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Video Player
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Chewie(
                controller: _chewieController,
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
