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
  String? _errorMessage;
  Video? _video;

  @override
  void initState() {
    super.initState();
    _loadVideoInfo();
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
            // Video Player (protegido, com token de curta duração)
            AspectRatio(
              aspectRatio: 16 / 9,
              child: SecureVideoPlayer(
                videoId: widget.videoId.toString(),
                videoTitle: _video?.title ?? 'Aula',
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
