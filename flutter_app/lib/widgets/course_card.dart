import 'package:flutter/material.dart';
import '../models/course_model.dart';

class CourseCard extends StatelessWidget {
  final Course course;
  final VoidCallback onTap;

  const CourseCard({
    Key? key,
    required this.course,
    required this.onTap,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.08),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        overflow: ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Thumbnail
              Container(
                width: double.infinity,
                height: 120,
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
                          size: 40,
                          color: Colors.grey[400],
                        ),
                      )
                    : null,
              ),

              // Content
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Category & Level
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
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
                              fontFamily: 'Inter',
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: _getLevelColor(course.level).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            _getLevelLabel(course.level),
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: _getLevelColor(course.level),
                              fontFamily: 'Inter',
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),

                    // Title
                    Text(
                      course.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        fontFamily: 'Poppins',
                      ),
                    ),
                    const SizedBox(height: 8),

                    // Description
                    Text(
                      course.description,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                        fontFamily: 'Inter',
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Footer: Instructor & Price
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        // Instructor
                        Expanded(
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 16,
                                backgroundImage: course.instructorAvatar != null
                                    ? NetworkImage(course.instructorAvatar!)
                                    : null,
                                child: course.instructorAvatar == null
                                    ? Text(
                                        (course.instructorName ?? 'P')[0].toUpperCase(),
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  course.instructorName ?? 'Instrutor',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontFamily: 'Inter',
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                        // Price
                        Text(
                          course.isFree
                              ? 'Grátis'
                              : 'R\$ ${course.price.toStringAsFixed(2)}',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: course.isFree
                                ? const Color(0xFF51CF66)
                                : const Color(0xFF0066FF),
                            fontFamily: 'Poppins',
                          ),
                        ),
                      ],
                    ),

                    // Rating (if available)
                    if (course.rating != null) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          ...List.generate(
                            5,
                            (index) => Icon(
                              index < course.rating!.toInt()
                                  ? Icons.star_rounded
                                  : Icons.star_outline_rounded,
                              size: 14,
                              color: Colors.amber,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${course.rating!.toStringAsFixed(1)}',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              fontFamily: 'Inter',
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getLevelColor(String level) {
    switch (level) {
      case 'beginner':
        return const Color(0xFF51CF66);
      case 'intermediate':
        return const Color(0xFFFFC107);
      case 'advanced':
        return const Color(0xFFFF6B6B);
      default:
        return Colors.grey;
    }
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
