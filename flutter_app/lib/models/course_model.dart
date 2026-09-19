class Course {
  final int id;
  final String title;
  final String description;
  final String? thumbnailUrl;
  final double price;
  final String type; // free, paid
  final int? durationMinutes;
  final String category;
  final String level; // beginner, intermediate, advanced
  final double? rating;
  final bool featured;
  final String status; // draft, published, archived
  final int instructorId;
  final String? instructorName;
  final String? instructorAvatar;
  final List<Video>? videos;
  final int? studentCount;
  final bool? isSubscribed;
  final double? progressPercentage;
  final DateTime createdAt;

  Course({
    required this.id,
    required this.title,
    required this.description,
    this.thumbnailUrl,
    required this.price,
    required this.type,
    this.durationMinutes,
    required this.category,
    required this.level,
    this.rating,
    required this.featured,
    required this.status,
    required this.instructorId,
    this.instructorName,
    this.instructorAvatar,
    this.videos,
    this.studentCount,
    this.isSubscribed,
    this.progressPercentage,
    required this.createdAt,
  });

  factory Course.fromJson(Map<String, dynamic> json) {
    return Course(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      thumbnailUrl: json['thumbnail_url'] as String?,
      price: _toDouble(json['price']) ?? 0.0,
      type: json['type'] as String? ?? 'free',
      durationMinutes: json['duration_minutes'] as int?,
      category: json['category'] as String? ?? '',
      level: json['level'] as String? ?? 'beginner',
      rating: _toDouble(json['rating']),
      featured: json['featured'] as bool? ?? false,
      status: json['status'] as String? ?? 'draft',
      instructorId: json['instructor_id'] as int? ?? 0,
      instructorName: json['instructor']?['name'] as String?,
      instructorAvatar: json['instructor']?['avatar_url'] as String?,
      videos: (json['videos'] as List<dynamic>?)
          ?.map((v) => Video.fromJson(v as Map<String, dynamic>))
          .toList(),
      studentCount: json['student_count'] as int?,
      isSubscribed: json['is_subscribed'] as bool?,
      progressPercentage: _toDouble(json['progress_percentage']),
      createdAt: json['created_at'] != null
          ? (DateTime.tryParse(json['created_at'] as String) ?? DateTime.now())
          : DateTime.now(),
    );
  }

  /// Converte valores que podem vir como String (ex: cast 'decimal' do
  /// Laravel serializa como texto no JSON) ou num para double.
  static double? _toDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'thumbnail_url': thumbnailUrl,
      'price': price,
      'type': type,
      'duration_minutes': durationMinutes,
      'category': category,
      'level': level,
      'rating': rating,
      'featured': featured,
      'status': status,
      'instructor_id': instructorId,
      'created_at': createdAt.toIso8601String(),
    };
  }

  bool get isFree => type == 'free';
  bool get isPaid => type == 'paid';
  bool get isPublished => status == 'published';
  String get difficultyLevel => level;

  Course copyWith({
    int? id,
    String? title,
    String? description,
    String? thumbnailUrl,
    double? price,
    String? type,
    int? durationMinutes,
    String? category,
    String? level,
    double? rating,
    bool? featured,
    String? status,
    int? instructorId,
    String? instructorName,
    String? instructorAvatar,
    List<Video>? videos,
    int? studentCount,
    bool? isSubscribed,
    double? progressPercentage,
    DateTime? createdAt,
  }) {
    return Course(
      id: id ?? this.id,
      title: title ?? this.title,
      description: description ?? this.description,
      thumbnailUrl: thumbnailUrl ?? this.thumbnailUrl,
      price: price ?? this.price,
      type: type ?? this.type,
      durationMinutes: durationMinutes ?? this.durationMinutes,
      category: category ?? this.category,
      level: level ?? this.level,
      rating: rating ?? this.rating,
      featured: featured ?? this.featured,
      status: status ?? this.status,
      instructorId: instructorId ?? this.instructorId,
      instructorName: instructorName ?? this.instructorName,
      instructorAvatar: instructorAvatar ?? this.instructorAvatar,
      videos: videos ?? this.videos,
      studentCount: studentCount ?? this.studentCount,
      isSubscribed: isSubscribed ?? this.isSubscribed,
      progressPercentage: progressPercentage ?? this.progressPercentage,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}

class Video {
  final int id;
  final int courseId;
  final String title;
  final String? description;
  final String videoUrl;
  final int durationSeconds;
  final int order;
  final String quality; // 480p, 720p, 1080p
  final String? thumbnailUrl;
  final String? materialUrl;
  final String status;
  final DateTime createdAt;

  Video({
    required this.id,
    required this.courseId,
    required this.title,
    this.description,
    required this.videoUrl,
    required this.durationSeconds,
    required this.order,
    required this.quality,
    this.thumbnailUrl,
    this.materialUrl,
    required this.status,
    required this.createdAt,
  });

  factory Video.fromJson(Map<String, dynamic> json) {
    return Video(
      id: json['id'] as int,
      courseId: json['course_id'] as int,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      videoUrl: json['video_url'] as String? ?? '',
      durationSeconds: json['duration_seconds'] as int? ?? 0,
      order: json['order'] as int? ?? 0,
      quality: json['quality'] as String? ?? '720p',
      thumbnailUrl: json['thumbnail_url'] as String?,
      materialUrl: json['material_url'] as String?,
      status: json['status'] as String? ?? 'draft',
      createdAt: json['created_at'] != null
          ? (DateTime.tryParse(json['created_at'] as String) ?? DateTime.now())
          : DateTime.now(),
    );
  }

  String get durationFormatted {
    final minutes = durationSeconds ~/ 60;
    final seconds = durationSeconds % 60;
    return '${minutes}m ${seconds}s';
  }

  String get durationMinutes => (durationSeconds ~/ 60).toString();
}
