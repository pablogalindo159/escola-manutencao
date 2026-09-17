import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/course_provider.dart';
import '../models/course_model.dart';

class CourseProgressScreen extends StatefulWidget {
  final int courseId;

  const CourseProgressScreen({
    Key? key,
    required this.courseId,
  }) : super(key: key);

  @override
  State<CourseProgressScreen> createState() => _CourseProgressScreenState();
}

class _CourseProgressScreenState extends State<CourseProgressScreen> {
  @override
  void initState() {
    super.initState();
    _loadProgress();
  }

  Future<void> _loadProgress() async {
    final videoProvider = context.read<VideoProgressProvider>();
    await videoProvider.loadCourseProgress(widget.courseId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Progresso do Curso'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
      ),
      body: Consumer2<CourseProvider, VideoProgressProvider>(
        builder: (context, courseProvider, videoProvider, _) {
          final course = courseProvider.selectedCourse;

          if (course == null || course.videos == null) {
            return const Center(
              child: Text('Nenhuma aula encontrada'),
            );
          }

          final videos = course.videos!;
          final completedCount = videos
              .where((v) => videoProvider.isVideoCompleted(v.id))
              .length;
          final totalProgress =
              (completedCount / videos.length * 100).toStringAsFixed(0);

          return RefreshIndicator(
            onRefresh: _loadProgress,
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
                          'Seu Progresso',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                            fontFamily: 'Poppins',
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Progress Bar
                        Row(
                          children: [
                            Expanded(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: LinearProgressIndicator(
                                  value: completedCount / videos.length,
                                  minHeight: 12,
                                  valueColor:
                                      const AlwaysStoppedAnimation<Color>(
                                    Colors.white,
                                  ),
                                  backgroundColor:
                                      Colors.white.withOpacity(0.3),
                                ),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Text(
                              '$totalProgress%',
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                                fontFamily: 'Poppins',
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 20),

                        // Stats
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            _buildStatItem(
                              label: 'Concluídas',
                              value: completedCount.toString(),
                            ),
                            _buildStatItem(
                              label: 'Restantes',
                              value: (videos.length - completedCount)
                                  .toString(),
                            ),
                            _buildStatItem(
                              label: 'Total',
                              value: videos.length.toString(),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // Videos List
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Aulas do Curso',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Poppins',
                          ),
                        ),
                        const SizedBox(height: 16),
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: videos.length,
                          itemBuilder: (context, index) {
                            final video = videos[index];
                            final progress =
                                videoProvider.getProgress(video.id);
                            final isCompleted = progress >= 80;

                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: isCompleted
                                      ? const Color(0xFF51CF66)
                                      : Colors.grey[200]!,
                                  width: isCompleted ? 2 : 1,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.04),
                                    blurRadius: 4,
                                  ),
                                ],
                              ),
                              child: Material(
                                color: Colors.transparent,
                                child: InkWell(
                                  onTap: () {
                                    Navigator.of(context).pushNamed(
                                      '/video-player',
                                      arguments: {
                                        'courseId': widget.courseId,
                                        'videoId': video.id,
                                      },
                                    );
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: Padding(
                                    padding: const EdgeInsets.all(16),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        // Title & Status
                                        Row(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment:
                                                    CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    '${index + 1}. ${video.title}',
                                                    style: const TextStyle(
                                                      fontSize: 14,
                                                      fontWeight:
                                                          FontWeight.w600,
                                                      fontFamily: 'Poppins',
                                                    ),
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis,
                                                  ),
                                                  const SizedBox(height: 4),
                                                  Text(
                                                    video.durationFormatted,
                                                    style: TextStyle(
                                                      fontSize: 12,
                                                      color: Colors.grey[600],
                                                      fontFamily: 'Inter',
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                            const SizedBox(width: 12),
                                            if (isCompleted)
                                              Container(
                                                padding:
                                                    const EdgeInsets.symmetric(
                                                  horizontal: 8,
                                                  vertical: 4,
                                                ),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF51CF66)
                                                      .withOpacity(0.1),
                                                  borderRadius:
                                                      BorderRadius.circular(6),
                                                ),
                                                child: const Text(
                                                  'Concluída',
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w600,
                                                    color: Color(0xFF51CF66),
                                                    fontFamily: 'Inter',
                                                  ),
                                                ),
                                              ),
                                          ],
                                        ),
                                        const SizedBox(height: 12),

                                        // Progress Bar
                                        ClipRRect(
                                          borderRadius:
                                              BorderRadius.circular(6),
                                          child: LinearProgressIndicator(
                                            value: progress / 100,
                                            minHeight: 6,
                                            valueColor:
                                                AlwaysStoppedAnimation<Color>(
                                              isCompleted
                                                  ? const Color(0xFF51CF66)
                                                  : const Color(0xFF0066FF),
                                            ),
                                            backgroundColor: Colors.grey[200],
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Text(
                                          '${progress.toStringAsFixed(0)}% assistido',
                                          style: TextStyle(
                                            fontSize: 11,
                                            color: Colors.grey[600],
                                            fontFamily: 'Inter',
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),

                  // Completion Message
                  if (completedCount == videos.length)
                    Padding(
                      padding: const EdgeInsets.all(24),
                      child: Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: const Color(0xFF51CF66).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: const Color(0xFF51CF66),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Icon(
                              Icons.celebration,
                              color: Color(0xFF51CF66),
                              size: 32,
                            ),
                            const SizedBox(height: 12),
                            const Text(
                              'Parabéns! 🎉',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                fontFamily: 'Poppins',
                                color: Color(0xFF51CF66),
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Você concluiu todas as aulas deste curso!',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.grey[700],
                                fontFamily: 'Inter',
                              ),
                            ),
                            const SizedBox(height: 16),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton(
                                onPressed: () {
                                  // TODO: Gerar certificado
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF51CF66),
                                ),
                                child: const Text('Gerar Certificado'),
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
        },
      ),
    );
  }

  Widget _buildStatItem({
    required String label,
    required String value,
  }) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: Colors.white,
            fontFamily: 'Poppins',
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            color: Colors.white70,
            fontFamily: 'Inter',
          ),
        ),
      ],
    );
  }
}
