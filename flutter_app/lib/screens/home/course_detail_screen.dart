import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../providers/course_provider.dart';
import '../video/video_player_screen.dart';
import '../../services/api_service.dart';
import '../payment/pix_payment_screen.dart';

class CourseDetailScreen extends StatefulWidget {
  final int courseId;

  const CourseDetailScreen({
    Key? key,
    required this.courseId,
  }) : super(key: key);

  @override
  State<CourseDetailScreen> createState() => _CourseDetailScreenState();
}

class _CourseDetailScreenState extends State<CourseDetailScreen> {
  bool _isProcessingPayment = false;

  @override
  void initState() {
    super.initState();
    _loadCourse();
  }

  Future<void> _loadCourse() async {
    final provider = context.read<CourseProvider>();
    await provider.selectCourse(widget.courseId);
    if (provider.selectedCourse?.isSubscribed == true && mounted) {
      // Carrega progresso só se o usuário já está inscrito (endpoint exige)
      await context.read<VideoProgressProvider>().loadCourseProgress(widget.courseId);
    }
  }

  Future<void> _subscribeToCourse() async {
    final provider = context.read<CourseProvider>();
    final success = await provider.subscribeToCourse(widget.courseId);

    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Inscrito com sucesso!'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  /// Curso pago: cria a cobrança no Mercado Pago e abre a página de
  /// pagamento no navegador (nunca dentro do app - o app não lida com
  /// dado de cartão nenhum). O acesso libera sozinho quando o
  /// pagamento for aprovado (webhook no backend), sem precisar voltar
  /// pro app pra confirmar nada.
  Future<void> _checkoutCourse() async {
    setState(() => _isProcessingPayment = true);

    // Mesmo método configurado no admin do site.
    // Se não conseguir consultar, usa o Checkout Pro (fluxo que já existia).
    String method = 'checkout_pro';
    try {
      method = await ApiService().getPaymentMethod();
    } catch (_) {}
    if (!mounted) return;

    if (method == 'pix_transparente') {
      setState(() => _isProcessingPayment = false);
      final course = context.read<CourseProvider>().selectedCourse;
      await Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => PixPaymentScreen(
            courseId: widget.courseId,
            courseTitle: course?.title ?? '',
            price: course?.price ?? 0,
          ),
        ),
      );
      // Recarrega o curso: se o pagamento aprovou, o botão vira "Já inscrito".
      if (mounted) await _loadCourse();
      return;
    }

    final provider = context.read<CourseProvider>();
    final initPoint = await provider.checkoutCourse(widget.courseId);

    if (!mounted) return;
    setState(() => _isProcessingPayment = false);

    if (initPoint == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage ?? 'Erro ao iniciar pagamento'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final uri = Uri.parse(initPoint);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Assim que o pagamento for aprovado, o curso libera automaticamente.'),
          ),
        );
      }
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Não foi possível abrir a página de pagamento.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalhes do Curso'),
        elevation: 0,
        backgroundColor: const Color(0xFF0066FF),
      ),
      body: Consumer<CourseProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          final course = provider.selectedCourse;

          if (course == null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.error_outline,
                    size: 64,
                    color: Colors.red,
                  ),
                  const SizedBox(height: 16),
                  const Text('Erro ao carregar curso'),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    onPressed: _loadCourse,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Tentar novamente'),
                  ),
                ],
              ),
            );
          }

          return SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Thumbnail
                Container(
                  width: double.infinity,
                  height: 200,
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    image: course.thumbnailUrl != null
                        ? DecorationImage(
                            image: NetworkImage(course.thumbnailUrl!),
                            fit: BoxFit.cover,
                          )
                        : null,
                  ),
                  child: course.thumbnailUrl == null
                      ? Center(
                          child: Icon(
                            Icons.image_outlined,
                            size: 80,
                            color: Colors.grey[400],
                          ),
                        )
                      : null,
                ),

                // Content
                Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Title
                      Text(
                        course.title,
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Poppins',
                        ),
                      ),
                      const SizedBox(height: 8),

                      // Category & Level Row
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0066FF).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              course.category,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF0066FF),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.amber.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              _getLevelLabel(course.level),
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: Colors.amber,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Instructor Info
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 24,
                            backgroundImage: course.instructorAvatar != null
                                ? NetworkImage(course.instructorAvatar!)
                                : null,
                            child: course.instructorAvatar == null
                                ? Text(
                                    (course.instructorName ?? 'I')[0].toUpperCase(),
                                    style: const TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  )
                                : null,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Instrutor',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey,
                                    fontFamily: 'Inter',
                                  ),
                                ),
                                Text(
                                  course.instructorName ?? 'Sem nome',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                    fontFamily: 'Poppins',
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),

                      // Description
                      const Text(
                        'Sobre o curso',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          fontFamily: 'Poppins',
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        course.description,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[700],
                          fontFamily: 'Inter',
                          height: 1.6,
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Course Stats
                      Row(
                        children: [
                          _buildStat(
                            icon: Icons.video_library_outlined,
                            label: 'Vídeos',
                            value: '${course.videos?.length ?? 0}',
                          ),
                          const SizedBox(width: 24),
                          _buildStat(
                            icon: Icons.group_outlined,
                            label: 'Alunos',
                            value: '${course.studentCount ?? 0}',
                          ),
                          const SizedBox(width: 24),
                          _buildStat(
                            icon: Icons.star_outlined,
                            label: 'Rating',
                            value: course.rating?.toStringAsFixed(1) ?? '-',
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),

                      // Videos List
                      if (course.videos != null && course.videos!.isNotEmpty) ...[
                        const Text(
                          'Aulas do Curso',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'Poppins',
                          ),
                        ),
                        const SizedBox(height: 12),
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: course.videos!.length,
                          itemBuilder: (context, index) {
                            final video = course.videos![index];
                            final progressProvider =
                                context.watch<VideoProgressProvider>();
                            final isCompleted =
                                progressProvider.isVideoCompleted(video.id);
                            final isStarted =
                                progressProvider.isVideoStarted(video.id);
                            return InkWell(
                              borderRadius: BorderRadius.circular(8),
                              onTap: () {
                                if (course.isSubscribed == true) {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => VideoPlayerScreen(
                                        courseId: course.id,
                                        videoId: video.id,
                                      ),
                                    ),
                                  );
                                } else {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(
                                      content: Text(
                                        'Inscreva-se no curso para assistir às aulas',
                                      ),
                                    ),
                                  );
                                }
                              },
                              child: Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.grey[100],
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(6),
                                    child: video.thumbnailUrl != null &&
                                            video.thumbnailUrl!.isNotEmpty
                                        ? Image.network(
                                            video.thumbnailUrl!,
                                            width: 64,
                                            height: 48,
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) =>
                                                _videoThumbnailFallback(),
                                          )
                                        : _videoThumbnailFallback(),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          '${index + 1}. ${video.title}',
                                          style: const TextStyle(
                                            fontSize: 14,
                                            fontWeight: FontWeight.w600,
                                            fontFamily: 'Poppins',
                                          ),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        Row(
                                          children: [
                                            Text(
                                              video.durationFormatted,
                                              style: TextStyle(
                                                fontSize: 12,
                                                color: Colors.grey[600],
                                                fontFamily: 'Inter',
                                              ),
                                            ),
                                            if (isCompleted) ...[
                                              const SizedBox(width: 6),
                                              Text(
                                                '· Concluído',
                                                style: TextStyle(
                                                  fontSize: 12,
                                                  color: Colors.green[700],
                                                  fontFamily: 'Inter',
                                                ),
                                              ),
                                            ] else if (isStarted) ...[
                                              const SizedBox(width: 6),
                                              const Text(
                                                '· Continuar',
                                                style: TextStyle(
                                                  fontSize: 12,
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
                                  if (course.isSubscribed != true)
                                    Icon(
                                      Icons.lock_outline,
                                      color: Colors.grey[400],
                                      size: 18,
                                    )
                                  else if (isCompleted)
                                    Icon(
                                      Icons.check_circle,
                                      color: Colors.green[600],
                                      size: 20,
                                    ),
                                ],
                              ),
                              ),
                            );
                          },
                        ),
                        const SizedBox(height: 24),
                      ],

                      // Price
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Preço do curso',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey,
                                    fontFamily: 'Inter',
                                  ),
                                ),
                                Text(
                                  course.isFree
                                      ? 'Grátis'
                                      : 'R\$ ${course.price.toStringAsFixed(2)}',
                                  style: const TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.bold,
                                    fontFamily: 'Poppins',
                                    color: Color(0xFF0066FF),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Subscribe / Buy Button
                      SizedBox(
                        width: double.infinity,
                        height: 56,
                        child: ElevatedButton(
                          onPressed: course.isSubscribed == true || _isProcessingPayment
                              ? null
                              : (course.isFree ? _subscribeToCourse : _checkoutCourse),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF0066FF),
                            disabledBackgroundColor: Colors.grey[300],
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _isProcessingPayment
                              ? const SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(
                                    color: Colors.white,
                                    strokeWidth: 2.5,
                                  ),
                                )
                              : Text(
                                  course.isSubscribed == true
                                      ? 'Já inscrito'
                                      : (course.isFree ? 'Inscrever-se agora' : 'Comprar com Mercado Pago'),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                    fontFamily: 'Poppins',
                                  ),
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _videoThumbnailFallback() {
    return Container(
      width: 64,
      height: 48,
      color: const Color(0xFF0066FF).withOpacity(0.1),
      child: const Icon(
        Icons.play_circle_outline,
        color: Color(0xFF0066FF),
      ),
    );
  }

  Widget _buildStat({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(8),
        ),
        child: Column(
          children: [
            Icon(
              icon,
              color: const Color(0xFF0066FF),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                fontFamily: 'Poppins',
              ),
            ),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontFamily: 'Inter',
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _getLevelLabel(String level) {
    switch (level) {
      case 'beginner':
        return 'Iniciante';
      case 'intermediate':
        return 'Intermediário';
      case 'advanced':
        return 'Avançado';
      default:
        return level;
    }
  }
}
